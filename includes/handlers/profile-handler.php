<?php
/* INFO: Handles profile updates submitted from pages/profile.php.
 * Covers two actions (distinguished by the hidden "action" field):
 *   - update_profile:  personal info + shipping address
 *   - update_username: username change (validated via user-profile-functions.php)
 */

/* INFO: Linked Files:

    config/db.php
    user-profile-functions.php

*/

session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions/user-profile-functions.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: ../../pages/homepage.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../pages/profile.php");
    exit;
}

$u_id   = $_SESSION['u_id'];
$action = $_POST['action'] ?? '';

/* ---------------- Personal Info / Address ---------------- */
if ($action === 'update_profile') {
    $profile_data = [
        'full_name'     => trim($_POST['full_name'] ?? ''),
        'phone_number'  => trim($_POST['phone_number'] ?? ''),
        'address_line1' => trim($_POST['address_line1'] ?? ''),
        'address_line2' => trim($_POST['address_line2'] ?? ''),
        'city'          => trim($_POST['city'] ?? ''),
        'province'      => trim($_POST['province'] ?? ''),
        'postal_code'   => trim($_POST['postal_code'] ?? '')
    ];

    if (update_user_profile($pdo, $u_id, $profile_data)) {
        $_SESSION['profile_success'] = 'Profile updated successfully!';
    } else {
        $_SESSION['profile_error'] = 'Failed to update profile. Please try again.';
    }

    header("Location: ../../pages/profile.php");
    exit;
}

/* ---------------- Username Change ---------------- */
if ($action === 'update_username') {
    $new_username = trim($_POST['new_username'] ?? '');
    $result = update_username($pdo, $u_id, $new_username);

    if ($result['success']) {
        $_SESSION['username'] = $new_username;
        $_SESSION['username_success'] = 'Username updated successfully!';
    } else {
        $_SESSION['username_error'] = $result['error'];
    }

    header("Location: ../../pages/profile.php");
    exit;
}

// Unrecognized action — just bounce back
header("Location: ../../pages/profile.php");
exit;