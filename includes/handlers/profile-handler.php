<?php
/* INFO: Handles profile updates submitted from pages/profile.php.
 * Validates inputs and updates the user_profiles table.
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

$u_id = $_SESSION['u_id'];

$first_name    = trim($_POST['first_name'] ?? '');
$last_name     = trim($_POST['last_name'] ?? '');
$phone_number  = trim($_POST['phone_number'] ?? '');
$address_line1 = trim($_POST['address_line1'] ?? '');
$address_line2 = trim($_POST['address_line2'] ?? '');
$city          = trim($_POST['city'] ?? '');
$province      = trim($_POST['province'] ?? '');
$postal_code   = trim($_POST['postal_code'] ?? '');

$success = update_user_profile($pdo, $u_id, [
    'full_name'     => trim($first_name . ' ' . $last_name),
    'phone_number'  => $phone_number,
    'address_line1' => $address_line1,
    'address_line2' => $address_line2,
    'city'          => $city,
    'province'      => $province,
    'postal_code'   => $postal_code
]);

if ($success) {
    $_SESSION['profile_success'] = 'Profile updated successfully. Your details will autofill at checkout!';
} else {
    $_SESSION['profile_error'] = 'Failed to update profile. Please try again.';
}

header("Location: ../../pages/profile.php");
exit;