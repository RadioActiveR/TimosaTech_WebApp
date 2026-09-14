<?php
/* INFO: Handles the checkout form submission from pages/checkout.php.
 * Mirrors the redirect-back-with-session-error pattern used by auth-handler.php.
 *
 * INFO (cart select-before-checkout): only checks out the items the user
 * selected in the cart modal (via get_checkout_cart_items(), same session
 * key checkout.php sets), so anything left unchecked stays in the cart.
 */

/* INFO: Linked Files:

    config/db.php
    user-profile-functions.php

*/

session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../functions/cart-functions.php';
require __DIR__ . '/../functions/order-functions.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: ../../pages/homepage.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../pages/checkout.php");
    exit;
}

$u_id = $_SESSION['u_id'];

function redirect_to_checkout(string $error): void {
    $_SESSION['checkout_error'] = $error;
    $_SESSION['checkout_old_input'] = [
        'recipient_name' => trim($_POST['recipient_name'] ?? ''),
        'phone_number'   => trim($_POST['phone_number'] ?? ''),
        'address_line1'  => trim($_POST['address_line1'] ?? ''),
        'address_line2'  => trim($_POST['address_line2'] ?? ''),
        'city'           => trim($_POST['city'] ?? ''),
        'province'       => trim($_POST['province'] ?? ''),
        'postal_code'    => trim($_POST['postal_code'] ?? ''),
        'payment_method' => trim($_POST['payment_method'] ?? ''),
    ];
    header("Location: ../../pages/checkout.php");
    exit;
}

$recipient_name = trim($_POST['recipient_name'] ?? '');
$phone_number   = trim($_POST['phone_number'] ?? '');
$address_line1  = trim($_POST['address_line1'] ?? '');
$address_line2  = trim($_POST['address_line2'] ?? '');
$city           = trim($_POST['city'] ?? '');
$province       = trim($_POST['province'] ?? '');
$postal_code    = trim($_POST['postal_code'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');

$valid_payment_methods = ['cod', 'gcash', 'bank_transfer'];

if ($recipient_name === '' || $phone_number === '' || $address_line1 === '' ||
    $city === '' || $province === '' || $postal_code === '') {
    redirect_to_checkout('Please fill in all required fields.');
}
if (!in_array($payment_method, $valid_payment_methods, true)) {
    redirect_to_checkout('Please select a valid payment method.');
}

// Never trust totals/quantities from the client — re-pull the selected
// items fresh from the DB (falls back to the whole cart if nothing was
// specifically selected — see get_checkout_cart_items()).
$cart_items = get_checkout_cart_items($pdo, $u_id);
if (empty($cart_items)) {
    header("Location: ../../pages/shop.php");
    exit;
}

foreach ($cart_items as $item) {
    if ($item['quantity'] > $item['stock']) {
        redirect_to_checkout('"' . $item['name'] . '" no longer has enough stock. Please update your cart.');
    }
}

$order_id = create_order($pdo, $u_id, [
    'recipient_name' => $recipient_name,
    'phone_number'   => $phone_number,
    'address_line1'  => $address_line1,
    'address_line2'  => $address_line2,
    'city'           => $city,
    'province'       => $province,
    'postal_code'    => $postal_code,
    'payment_method' => $payment_method,
], $cart_items, get_shipping_fee());

if (!$order_id) {
    redirect_to_checkout('Something went wrong placing your order. Please try again.');
}

// Clear the selection now that it's been turned into an order — a fresh
// visit to the cart modal should default back to "everything selected".
unset($_SESSION['checkout_selected_items']);

header("Location: ../../pages/order-confirmation.php?order_id=" . urlencode($order_id));
exit;