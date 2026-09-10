<?php
/* INFO: Checkout page displaying selected cart items, auto-filling 
 * shipping information from user_profiles, and submitting to order-handler.php.
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cart-functions.php';
require_once __DIR__ . '/../includes/user-profile-functions.php';

// Redirect unauthenticated users
if (!isset($_SESSION['u_id'])) {
    header("Location: homepage.php");
    exit;
}

$u_id = $_SESSION['u_id'];

// Retrieve selected item IDs (passed via POST from cart modal/page or stored in SESSION)
$selected_cart_ids = $_POST['selected_items'] ?? $_SESSION['checkout_selected_items'] ?? [];

if (empty($selected_cart_ids)) {
    // If no items selected, redirect back to cart or product view
    header("Location: products.php");
    exit;
}

// Store selections in session for resilience across page refreshes
$_SESSION['checkout_selected_items'] = $selected_cart_ids;

// Fetch selected cart items
$checkout_items = get_cart_items($pdo, $u_id, $selected_cart_ids);

if (empty($checkout_items)) {
    header("Location: products.php");
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

$default_recipient = trim(($user_profile['first_name'] ?? '') . ' ' . ($user_profile['last_name'] ?? ''));
$default_phone     = $user_profile['phone_number'] ?? '';
$default_addr1     = $user_profile['address_line1'] ?? '';
$default_addr2     = $user_profile['address_line2'] ?? '';
$default_city      = $user_profile['city'] ?? '';
$default_province  = $user_profile['province'] ?? '';
$default_postal    = $user_profile['postal_code'] ?? '';

// Check for validation errors returned from order-handler.php
$checkout_error = $_SESSION['checkout_error'] ?? null;
unset($_SESSION['checkout_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout | Timosa Tech</title>
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .checkout-container {
      max-width: 1100px;
      margin: 2rem auto;
      padding: 0 1rem;
      display: grid;
      grid-template-columns: 1fr 400px;
      gap: 2rem;
    }
    .checkout-section {
      background: #181818;
      border: 1px solid #333;
      border-radius: 8px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }
    .checkout-section h2 {
      margin-top: 0;
      border-bottom: 1px solid #333;
      padding-bottom: 0.75rem;
      font-size: 1.25rem;
      color: #fff;
    }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    .full-width {
      grid-column: span 2;
    }
    .form-group {
      margin-bottom: 1rem;
    }
    .form-group label {
      display: block;
      margin-bottom: 0.4rem;
      color: #ccc;
      font-size: 0.9rem;
    }
    .form-group input, .form-group select {
      width: 100%;
      padding: 0.65rem;
      background: #222;
      border: 1px solid #444;
      color: #fff;
      border-radius: 4px;
      box-sizing: border-box;
    }
    .form-group input:focus, .form-group select:focus {
      outline: none;
      border-color: #007bff;
    }
    .payment-options {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
    .payment-option {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      background: #222;
      padding: 0.75rem 1rem;
      border: 1px solid #444;
      border-radius: 4px;
      cursor: pointer;
    }
    .payment-option input {
      width: auto;
    }
    .summary-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.75rem;
      font-size: 0.95rem;
    }
    .summary-item-title {
      font-weight: bold;
      color: #fff;
    }
    .summary-item-sub {
      color: #888;
      font-size: 0.85rem;
    }
    .summary-divider {
      border-top: 1px solid #333;
      margin: 1rem 0;
    }
    .summary-total {
      display: flex;
      justify-content: space-between;
      font-size: 1.2rem;
      font-weight: bold;
      color: #007bff;
    }
    .btn-submit-order {
      width: 100%;
      padding: 0.85rem;
      background: #28a745;
      color: #fff;
      border: none;
      border-radius: 4px;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      margin-top: 1.5rem;
      transition: background 0.2s;
    }
    .btn-submit-order:hover {
      background: #218838;
    }
    .alert-error {
      background: #721c24;
      color: #f8d7da;
      padding: 0.85rem;
      border-radius: 4px;
      margin-bottom: 1.5rem;
    }
    @media (max-width: 768px) {
      .checkout-container {
        grid-template-columns: 1fr;
      }
      .form-grid {
        grid-template-columns: 1fr;
      }
      .full-width {
        grid-column: span 1;
      }
    }
  </style>
</head>
<body>

  <div style="max-width: 1100px; margin: 1.5rem auto 0; padding: 0 1rem;">
    <h1>Checkout</h1>
    <?php if ($checkout_error): ?>
      <div class="alert-error"><?= htmlspecialchars($checkout_error) ?></div>
    <?php endif; ?>
  </div>

  <form action="../includes/order-handler.php" method="POST">
    <div class="checkout-container">
      
      <!-- Left Column: Shipping & Payment Information -->
      <div class="checkout-main">
        <div class="checkout-section">
          <h2>Shipping Address</h2>
          <div class="form-grid">
            <div class="form-group full-width">
              <label>Recipient Full Name *</label>
              <input type="text" name="recipient_name" value="<?= htmlspecialchars($default_recipient) ?>" required placeholder="e.g. Cornelius Timosa">
            </div>
            <div class="form-group full-width">
              <label>Phone Number *</label>
              <input type="text" name="phone_number" value="<?= htmlspecialchars($default_phone) ?>" required placeholder="e.g. 09123456789">
            </div>
            <div class="form-group full-width">
              <label>Address Line 1 *</label>
              <input type="text" name="address_line1" value="<?= htmlspecialchars($default_addr1) ?>" required placeholder="House/Unit No., Street Name, Barangay">
            </div>
            <div class="form-group full-width">
              <label>Address Line 2 (Optional)</label>
              <input type="text" name="address_line2" value="<?= htmlspecialchars($default_addr2) ?>" placeholder="Apt, Suite, Building, Landmark">
            </div>
            <div class="form-group">
              <label>City *</label>
              <input type="text" name="city" value="<?= htmlspecialchars($default_city) ?>" required placeholder="e.g. Dipolog City">
            </div>
            <div class="form-group">
              <label>Province *</label>
              <input type="text" name="province" value="<?= htmlspecialchars($default_province) ?>" required placeholder="e.g. Zamboanga del Norte">
            </div>
            <div class="form-group full-width">
              <label>Postal Code *</label>
              <input type="text" name="postal_code" value="<?= htmlspecialchars($default_postal) ?>" required placeholder="e.g. 7100">
            </div>
          </div>
        </div>

        <div class="checkout-section">
          <h2>Payment Method</h2>
          <div class="payment-options">
            <label class="payment-option">
              <input type="radio" name="payment_method" value="cod" checked>
              <div>
                <strong>Cash on Delivery (COD)</strong>
                <div style="font-size: 0.85rem; color: #888;">Pay upon receiving your order at your doorstep.</div>
              </div>
            </label>
            <label class="payment-option">
              <input type="radio" name="payment_method" value="gcash">
              <div>
                <strong>GCash</strong>
                <div style="font-size: 0.85rem; color: #888;">Direct e-wallet transfer. Details provided upon placement.</div>
              </div>
            </label>
            <label class="payment-option">
              <input type="radio" name="payment_method" value="bank_transfer">
              <div>
                <strong>Bank Transfer</strong>
                <div style="font-size: 0.85rem; color: #888;">Online banking or deposit transfer.</div>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Right Column: Order Summary -->
      <div class="checkout-sidebar">
        <div class="checkout-section">
          <h2>Order Summary</h2>
          
          <div style="max-height: 250px; overflow-y: auto; padding-right: 0.5rem; margin-bottom: 1rem;">
            <?php foreach ($checkout_items as $item): ?>
              <div class="summary-item">
                <div>
                  <div class="summary-item-title"><?= htmlspecialchars($item['name']) ?></div>
                  <div class="summary-item-sub">Qty: <?= (int)$item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></div>
                </div>
                <div>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="summary-divider"></div>

          <div class="summary-item">
            <span>Subtotal</span>
            <span>₱<?= number_format($subtotal, 2) ?></span>
          </div>
          <div class="summary-item">
            <span>Shipping Fee</span>
            <span>₱<?= number_format($shipping_fee, 2) ?></span>
          </div>

          <div class="summary-divider"></div>

          <div class="summary-total">
            <span>Total</span>
            <span>₱<?= number_format($grand_total, 2) ?></span>
          </div>

          <!-- Hidden inputs for backend execution -->
          <?php foreach ($checkout_items as $item): ?>
            <input type="hidden" name="selected_items[]" value="<?= htmlspecialchars($item['cart_id']) ?>">
          <?php endforeach; ?>

          <button type="submit" class="btn-submit-order">Place Order</button>
        </div>
      </div>

    </div>
  </form>

</body>
</html>