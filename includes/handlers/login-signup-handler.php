<?php
/* INFO: Handles both the Login form and the Sign Up form
 * (distinguished by the hidden "action" field).
 */

/* INFO: Linked Files:

    config/db.php

*/

session_start();
require __DIR__ . '/../../config/db.php';

function redirect_back(string $tab, ?string $error = null): void {
    if ($error !== null) {
        $_SESSION['auth_error'] = $error;
    }
    $_SESSION['auth_tab'] = $tab;
    
    // Preserve old inputs (excluding sensitive password fields)
    $_SESSION['auth_old_input'] = [
        'username' => trim($_POST['username'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'identity' => trim($_POST['identity'] ?? '')
    ];

    header("Location: ../pages/homepage.php");
    exit;
}

$action = $_POST['action'] ?? '';

/* ---------------- Sign Up ---------------- */
if ($action === 'signup') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        redirect_back('signup', 'Please fill in all fields.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_back('signup', 'Please enter a valid email address.');
    }
    if ($password !== $confirm) {
        redirect_back('signup', 'Passwords do not match.');
    }
    if (strlen($password) < 8) {
        redirect_back('signup', 'Password must be at least 8 characters.');
    }

    // Check if email or username is already taken
    $stmt = $pdo->prepare("SELECT u_id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        redirect_back('signup', 'An account with that email or username already exists.');
    }

    // Generate unique string ID for u_id
    $u_id = uniqid('usr_', true);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // 1. Insert into users table
        $stmt = $pdo->prepare(
            "INSERT INTO users (u_id, username, email, password_hash, role) VALUES (?, ?, ?, ?, 'user')"
        );
        $stmt->execute([$u_id, $username, $email, $hash]);

        // 2. Initialize empty row in user_profiles table
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

    header("Location: ../pages/homepage.php");
    exit;
}

/* ---------------- Log In ---------------- */
if ($action === 'login') {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identity === '' || $password === '') {
        redirect_back('login', 'Please enter your email/username and password.');
    }

    // Retrieve user matching either email OR username
    $stmt = $pdo->prepare("SELECT u_id, username, password_hash, role FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        redirect_back('login', 'Incorrect email/username or password.');
    }

    $_SESSION['u_id']      = $user['u_id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['user_role'] = $user['role'];

    // Redirect based on role
    if ($user['role'] === 'admin') {
        header("Location: /TimosaTech/admin/admin-portal.php");
    } else {
        header("Location: ../pages/homepage.php");
    }
    exit;
}