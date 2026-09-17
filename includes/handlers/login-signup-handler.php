<?php

/* INFO: Login / Sign Up Handler
 *
 * INFO: Linked Files:
 * 
 * NONE
 *
 * INFO: Is used by:
 * 
 * includes/modals/login-signup-modal.php
 */

session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../functions/user-profile-functions.php';

function is_safe_redirect(string $path): bool {
    return $path !== ''
        && str_starts_with($path, '/TimosaTech/')
        && !str_contains($path, '://')
        && !str_starts_with($path, '//');
}

function redirect_back(string $tab, ?string $error = null): void {
    if ($error !== null) {
        $_SESSION['auth_error'] = $error;
    }
    $_SESSION['auth_tab'] = $tab;
    
    $_SESSION['auth_old_input'] = [
        'username'    => trim($_POST['username'] ?? ''),
        'email'       => trim($_POST['email'] ?? ''),
        'identity'    => trim($_POST['identity'] ?? ''),
        'redirect_to' => trim($_POST['redirect_to'] ?? '')
    ];

    header("Location: ../../pages/homepage.php");
    exit;
}

$action = $_POST['action'] ?? '';

$redirect_to_input = trim($_POST['redirect_to'] ?? '');
$safe_redirect = is_safe_redirect($redirect_to_input) ? $redirect_to_input : '/TimosaTech/pages/homepage.php';

/* ---------------- SECTION: Sign Up ---------------- */
if ($action === 'signup') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        redirect_back('signup', 'Please fill in all fields.');
    }

    $username_error = validate_username($username);
    if ($username_error !== null) {
        redirect_back('signup', $username_error);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_back('signup', 'Please enter a valid email address.');
    }

    $allowed_domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com'];
    $email_domain = strtolower(substr(strrchr($email, '@'), 1));
    if (!in_array($email_domain, $allowed_domains, true)) {
        redirect_back('signup', 'Please use an email from a supported provider (Gmail, Yahoo, Outlook, Hotmail, or iCloud).');
    }

    if ($password !== $confirm) {
        redirect_back('signup', 'Passwords do not match.');
    }
    if (strlen($password) < 8) {
        redirect_back('signup', 'Password must be at least 8 characters.');
    }

    $stmt = $pdo->prepare("SELECT u_id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        redirect_back('signup', 'An account with that email or username already exists.');
    }

    $u_id = uniqid('usr_', true);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "INSERT INTO users (u_id, username, email, password_hash, role) VALUES (?, ?, ?, ?, 'user')"
        );
        $stmt->execute([$u_id, $username, $email, $hash]);

        $stmtProfile = $pdo->prepare(
            "INSERT INTO user_profiles (u_id) VALUES (?)"
        );
        $stmtProfile->execute([$u_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        redirect_back('signup', 'Registration failed. Please try again.');
    }

    $_SESSION['u_id']      = $u_id;
    $_SESSION['username']  = $username;
    $_SESSION['user_role'] = 'user';

    header("Location: " . $safe_redirect);
    exit;
}

/* ---------------- SECTION: Log In ---------------- */
if ($action === 'login') {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identity === '' || $password === '') {
        redirect_back('login', 'Please enter your email/username and password.');
    }

    $stmt = $pdo->prepare("SELECT u_id, username, password_hash, role FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        redirect_back('login', 'Incorrect email/username or password.');
    }

    $_SESSION['u_id']      = $user['u_id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['user_role'] = $user['role'];

    if ($user['role'] === 'admin') {
        header("Location: /TimosaTech/admin/admin-portal.php");
    } else {
        header("Location: " . $safe_redirect);
    }
    exit;
}