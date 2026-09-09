<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cart-functions.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: homepage.php");
    exit();
}

$u_id       = $_SESSION['u_id'];
$user_name  = $_SESSION['username'] ?? '';
$is_admin   = ($_SESSION['user_role'] ?? '') === 'admin';

/* INFO (cart select-before-checkout): the cart modal POSTs here with
 * selected_items[] = the cart_item_ids the user checked off. We re-validate
 * those against the user's actual cart (never trust IDs from the client),
 * stash the valid ones in session, then redirect back to ourselves via GET
 * (Post/Redirect/Get) so refreshing this page doesn't re-submit the form.
 * order-handler.php reads this same session key so a validation error on
 * the checkout form redirects back here without losing the selection.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_items'])) {
    $requested_ids = (array) $_POST['selected_items'];
    $valid_items   = get_cart_items_by_ids($pdo, $u_id, $requested_ids);

    if (!empty($valid_items)) {
        $_SESSION['checkout_selected_items'] = array_column($valid_items, 'cart_item_id');
    } else {
        unset($_SESSION['checkout_selected_items']);
    }

    header("Location: checkout.php");
    exit();
}

$page_title = "Timosa Tech - Checkout";

// Falls back to the whole cart if there's no valid selection in session
// (e.g. the user bookmarked/navigated to this page directly).
$cart_items = get_checkout_cart_items($pdo, $u_id);
if (empty($cart_items)) {
    header("Location: shop.php");
    exit();
}

$cart_total   = 0;
foreach ($cart_items as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}
$shipping_fee = 0.00;
$grand_total  = $cart_total + $shipping_fee;

// Prefill from saved profile, if the user has one on file
$stmt = $pdo->prepare("SELECT full_name, phone_number, address_line1, address_line2, city, province, postal_code FROM user_profiles WHERE u_id = ?");
$stmt->execute([$u_id]);
$profile = $stmt->fetch() ?: [];

$checkout_error = $_SESSION['checkout_error'] ?? null;
$old_input       = $_SESSION['checkout_old_input'] ?? [];
unset($_SESSION['checkout_error'], $_SESSION['checkout_old_input']);

$payment_methods = [
    'cod'           => 'Cash on Delivery',
    'gcash'         => 'GCash',
    'bank_transfer' => 'Bank Transfer',
];
$selected_payment = $old_input['payment_method'] ?? 'cod';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="../styles/styles.css">
</head>
<body>

  <header class="navbar">
    <div class="container">
      <div class="logo">
        <img class="img-logo" src="../images/TimosaTechLogo.png" alt="Logo">
        <a class="logoname1" href="../index.php">TIMOSA</a><a class="logoname2" href="../index.php">TECH</a>
      </div>
      <nav class="nav-links">
        <a href="../index.php">Home</a>
        <a href="shop.php">Shop</a>
        <a href="#">Services</a>
        <a href="#">About</a>
        <a href="#">Contact</a>
      </nav>
      <div class="nav-cta">
        <span class="nav-greeting">Hi, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?></span>
        <?php if ($is_admin): ?>
          <a href="admin-dashboard.php" class="btn btn-outline admin-nav-btn">Admin Dashboard</a>
        <?php endif; ?>
        <a href="../includes/logout.php" class="btn btn-outline">Log Out</a>
      </div>
    </div>
  </header>

  <main class="container checkout-main">
    <div class="checkout-header">
      <span class="section-tag">SECURE CHECKOUT</span>
      <h1>Complete Your Order</h1>
    </div>

    <?php if ($checkout_error): ?>
      <p class="auth-error" style="max-width: 700px; margin-bottom: 20px;"><?= htmlspecialchars($checkout_error) ?></p>
    <?php endif; ?>

    <div class="checkout-layout">
      <!-- Recipient / Payment Form -->
      <form method="post" action="../includes/order-handler.php" class="checkout-form">
        <section class="checkout-panel">
          <h3>Recipient Details</h3>
          <div class="auth-field">
            <label for="recipientName">Full Name *</label>
            <input type="text" id="recipientName" name="recipient_name"
                   value="<?= htmlspecialchars($old_input['recipient_name'] ?? $profile['full_name'] ?? '') ?>" required>
          </div>
          <div class="auth-field">
            <label for="phoneNumber">Phone Number *</label>
            <input type="text" id="phoneNumber" name="phone_number"
                   value="<?= htmlspecialchars($old_input['phone_number'] ?? $profile['phone_number'] ?? '') ?>" required>
          </div>
          <div class="auth-field">
            <label for="addressLine1">Address Line 1 *</label>
            <input type="text" id="addressLine1" name="address_line1"
                   value="<?= htmlspecialchars($old_input['address_line1'] ?? $profile['address_line1'] ?? '') ?>" required>
          </div>
          <div class="auth-field">
            <label for="addressLine2">Address Line 2</label>
            <input type="text" id="addressLine2" name="address_line2"
                   value="<?= htmlspecialchars($old_input['address_line2'] ?? $profile['address_line2'] ?? '') ?>">
          </div>
          <div style="display:flex; gap:15px; flex-wrap: wrap;">
            <div class="auth-field" style="flex:1; min-width: 140px;">
              <label for="city">City *</label>
              <input type="text" id="city" name="city"
                     value="<?= htmlspecialchars($old_input['city'] ?? $profile['city'] ?? '') ?>" required>
            </div>
            <div class="auth-field" style="flex:1; min-width: 140px;">
              <label for="province">Province *</label>
              <input type="text" id="province" name="province"
                     value="<?= htmlspecialchars($old_input['province'] ?? $profile['province'] ?? '') ?>" required>
            </div>
            <div class="auth-field" style="flex:1; min-width: 140px;">
              <label for="postalCode">Postal Code *</label>
              <input type="text" id="postalCode" name="postal_code"
                     value="<?= htmlspecialchars($old_input['postal_code'] ?? $profile['postal_code'] ?? '') ?>" required>
            </div>
          </div>
        </section>

        <section class="checkout-panel">
          <h3>Payment Method</h3>
          <div class="payment-options">
            <?php foreach ($payment_methods as $val => $label): ?>
              <label class="payment-option">
                <input type="radio" name="payment_method" value="<?= $val ?>" <?= $selected_payment === $val ? 'checked' : '' ?> required>
                <span><?= htmlspecialchars($label) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </section>

        <button type="submit" class="btn btn-primary auth-submit checkout-submit-btn">Place Order</button>
      </form>

      <!-- Order Summary -->
      <aside class="checkout-summary">
        <h3>Order Summary</h3>
        <div class="checkout-summary-items">
          <?php foreach ($cart_items as $item):
            $img_src = !empty($item['image_data'])
              ? 'data:' . htmlspecialchars($item['mime_type'] ?? 'image/png') . ';base64,' . $item['image_data']
              : '../images/workstation-rig.png';
          ?>
            <div class="summary-item">
              <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($item['name']) ?>">
              <div class="summary-item-info">
                <h4><?= htmlspecialchars($item['name']) ?></h4>
                <span>Qty: <?= intval($item['quantity']) ?> &times; $<?= number_format($item['price'], 2) ?></span>
              </div>
              <div class="summary-item-subtotal">$<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="checkout-summary-totals">
          <div class="summary-row"><span>Subtotal</span><span>$<?= number_format($cart_total, 2) ?></span></div>
          <div class="summary-row"><span>Shipping</span><span><?= $shipping_fee > 0 ? '$' . number_format($shipping_fee, 2) : 'Free' ?></span></div>
          <div class="summary-row summary-total"><span>Total</span><span>$<?= number_format($grand_total, 2) ?></span></div>
        </div>
      </aside>
    </div>
  </main>

</body>
</html>