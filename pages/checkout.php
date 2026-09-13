<?php
/* INFO: Checkout page displaying selected cart items, auto-filling 
 * shipping information from user_profiles, and submitting to order-handler.php.
 */

/* INFO: Linked Files:

    config/db.php
    includes/cart-functions.php
    includes/user-profile-functions.php

*/

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions/cart-functions.php';
require_once __DIR__ . '/../includes/functions/user-profile-functions.php';
require_once __DIR__ . '/../includes/functions/site-control-functions.php';
require_once __DIR__ . '/../helpers/icons.php';

// Redirect unauthenticated users
if (!isset($_SESSION['u_id'])) {
    header("Location: homepage.php");
    exit;
}

$u_id = $_SESSION['u_id'];
$page_hidden = is_page_hidden($pdo, 'checkout');

// Retrieve selected item IDs (passed via POST from cart modal/page or stored in SESSION)
$selected_cart_ids = $_POST['selected_items'] ?? $_SESSION['checkout_selected_items'] ?? [];

if (empty($selected_cart_ids)) {
    // If no items selected, redirect back to cart or product view
    header("Location: shop.php");
    exit;
}

// Store selections in session for resilience across page refreshes
$_SESSION['checkout_selected_items'] = $selected_cart_ids;

// Fetch selected cart items
$checkout_items = get_cart_items_by_ids($pdo, $u_id, $selected_cart_ids);

if (empty($checkout_items)) {
    header("Location: shop.php");
    exit;
}

// Calculate subtotal
$subtotal = 0;
foreach ($checkout_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping_fee = 100.00; // Flat-rate shipping fee
$grand_total  = $subtotal + $shipping_fee;

// Fetch user profile data to auto-fill shipping fields
$user_profile = get_user_profile($pdo, $u_id);

$default_recipient = $user_profile['full_name'] ?? '';
$default_phone     = $user_profile['phone_number'] ?? '';
$default_addr1     = $user_profile['address_line1'] ?? '';
$default_addr2     = $user_profile['address_line2'] ?? '';
$default_city      = $user_profile['city'] ?? '';
$default_province  = $user_profile['province'] ?? '';
$default_postal    = $user_profile['postal_code'] ?? '';

// Check for validation errors returned from order-handler.php
$checkout_error = $_SESSION['checkout_error'] ?? null;
unset($_SESSION['checkout_error']);

$page_title   = "Timosa Tech - Checkout";
$current_page = 'checkout';
$is_logged_in = true; // this page already requires a session, see the redirect above
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="icon" type="image/png" href="../assets/images/TimosaTechLogo.png">
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/page-veil.css">
  <link rel="stylesheet" href="../assets/css/checkout.css">
</head>
<body>

  <!-- NAVBAR -->
  <?php
    require_once __DIR__ . '/../components/header.php';
  ?>

  <main class="checkout-main">
  <?php if ($page_hidden): ?>
    <h1 class="hidden"> HIDDEN </h1>
  <?php else: ?>
    <div class="container">

      <div class="checkout-header">
        <span class="section-tag">SECURE CHECKOUT</span>
        <h1>Checkout</h1>
      </div>

      <?php if ($checkout_error): ?>
        <p class="auth-error checkout-error-banner"><?= htmlspecialchars($checkout_error) ?></p>
      <?php endif; ?>

      <form action="../includes/handlers/order-handler.php" method="POST" class="checkout-layout">

        <!-- Left Column: Shipping & Payment Information -->
        <div class="checkout-form">
          <div class="checkout-panel">
            <h3>Shipping Address</h3>

            <div class="auth-field">
              <label for="recipient_name">Recipient Full Name *</label>
              <input type="text" id="recipient_name" name="recipient_name" value="<?= htmlspecialchars($default_recipient) ?>" required placeholder="e.g. Cornelius Timosa">
            </div>

            <div class="auth-field">
              <label for="phone_number">Phone Number *</label>
              <input type="text" id="phone_number" name="phone_number" value="<?= htmlspecialchars($default_phone) ?>" required placeholder="e.g. 09123456789">
            </div>

            <div class="auth-field">
              <label for="address_line1">Address Line 1 *</label>
              <input type="text" id="address_line1" name="address_line1" value="<?= htmlspecialchars($default_addr1) ?>" required placeholder="House/Unit No., Street Name, Barangay">
            </div>

            <div class="auth-field">
              <label for="address_line2">Address Line 2 (Optional)</label>
              <input type="text" id="address_line2" name="address_line2" value="<?= htmlspecialchars($default_addr2) ?>" placeholder="Apt, Suite, Building, Landmark">
            </div>

            <div class="form-grid-2">
              <div class="auth-field">
                <label for="city">City *</label>
                <input type="text" id="city" name="city" value="<?= htmlspecialchars($default_city) ?>" required placeholder="e.g. Dipolog City">
              </div>
              <div class="auth-field">
                <label for="province">Province *</label>
                <input type="text" id="province" name="province" value="<?= htmlspecialchars($default_province) ?>" required placeholder="e.g. Zamboanga del Norte">
              </div>
            </div>

            <div class="auth-field">
              <label for="postal_code">Postal Code *</label>
              <input type="text" id="postal_code" name="postal_code" value="<?= htmlspecialchars($default_postal) ?>" required placeholder="e.g. 7100">
            </div>
          </div>

          <div class="checkout-panel">
            <h3>Payment Method</h3>
            <div class="payment-options">
              <label class="payment-option">
                <input type="radio" name="payment_method" value="cod" checked>
                <div>
                  <strong>Cash on Delivery (COD)</strong>
                  <div class="payment-option-desc">Pay upon receiving your order at your doorstep.</div>
                </div>
              </label>
              <label class="payment-option">
                <input type="radio" name="payment_method" value="gcash">
                <div>
                  <strong>GCash</strong>
                  <div class="payment-option-desc">Direct e-wallet transfer. Details provided upon placement.</div>
                </div>
              </label>
              <label class="payment-option">
                <input type="radio" name="payment_method" value="bank_transfer">
                <div>
                  <strong>Bank Transfer</strong>
                  <div class="payment-option-desc">Online banking or deposit transfer.</div>
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- Right Column: Order Summary -->
        <div class="checkout-summary-col">
          <a href="shop.php" class="checkout-back-btn">&larr; Back to Shop</a>

          <aside class="checkout-summary">
          <h3>Order Summary</h3>

          <div class="checkout-summary-items">
            <?php foreach ($checkout_items as $item): ?>
              <div class="summary-item">
                <div class="summary-item-info">
                  <h4><?= htmlspecialchars($item['name']) ?></h4>
                  <span>Qty: <?= (int)$item['quantity'] ?> &times; ₱<?= number_format($item['price'], 2) ?></span>
                </div>
                <div class="summary-item-subtotal">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="checkout-summary-totals">
            <div class="summary-row"><span>Subtotal</span><span>₱<?= number_format($subtotal, 2) ?></span></div>
            <div class="summary-row"><span>Shipping Fee</span><span>₱<?= number_format($shipping_fee, 2) ?></span></div>
            <div class="summary-row summary-total"><span>Total</span><span>₱<?= number_format($grand_total, 2) ?></span></div>
          </div>

          <!-- Hidden inputs so order-handler.php receives exactly the items
               shown here, regardless of what's still in the rest of the cart. -->
          <?php foreach ($checkout_items as $item): ?>
            <input type="hidden" name="selected_items[]" value="<?= htmlspecialchars($item['cart_item_id']) ?>">
          <?php endforeach; ?>

          <button type="submit" class="btn btn-primary checkout-summary-submit">Place Order</button>
          </aside>
        </div>

      </form>
    </div>
  <?php endif; ?>
  </main>

  <!-- INFO: FOOTER SECTION -->
  <?php
    require_once __DIR__ . '/../components/footer.php';
  ?>

</body>
</html>