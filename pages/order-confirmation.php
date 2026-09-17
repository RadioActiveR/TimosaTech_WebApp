<?php

/* INFO: Linked Files:

    config/db.php

*/

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions/site-control-functions.php';
require_once __DIR__ . '/../includes/functions/cart-functions.php';
require_once __DIR__ . '/../helpers/icons.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: homepage.php");
    exit();
}

$u_id         = $_SESSION['u_id'];
$page_hidden  = is_page_hidden($pdo, 'order_confirmation');
$is_logged_in = true;
$user_name    = $_SESSION['username'] ?? '';
$is_admin     = ($_SESSION['user_role'] ?? '') === 'admin';
$cart_count   = get_cart_count($pdo, $u_id);
$order_id     = $_GET['order_id'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND u_id = ?");
$stmt->execute([$order_id, $u_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: shop.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

$page_title   = "Timosa Tech - Order Confirmation";
$current_page = 'order_confirmation';

$minimal_header = true;

$status_labels = [
    'pending'    => 'Pending',
    'processing' => 'Processing',
    'shipped'    => 'Shipped',
    'completed'  => 'Completed',
    'cancelled'  => 'Cancelled',
];
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
  <link rel="stylesheet" href="../assets/css/content-veil.css">
  <link rel="stylesheet" href="../assets/css/order-confirmation.css">
</head>
<body>

  <!-- SECTION: NAVBAR -->
  <?php
    require_once __DIR__ . '/../components/header.php';
  ?>

  <main class="container checkout-main">
  <?php if ($page_hidden): ?>
    <div class="center-container">
      <h1 class="hidden"> HIDDEN </h1>
      <h2 class="hidden-subtext"> Protocol 'CONTENT VEIL' active. Public routing disabled by Administrator. </h2>
    </div>
  <?php else: ?>
    <div class="order-confirm-banner">
      <span class="section-tag">ORDER PLACED</span>
      <h1>Thank you, <?= htmlspecialchars(explode(' ', $order['recipient_name'])[0]) ?>!</h1>
      <p>Your order <strong class="order-id-copy-wrap">#<?= htmlspecialchars($order['order_id']) ?><button type="button" class="copy-order-id-btn" data-copy="<?= htmlspecialchars($order['order_id']) ?>" aria-label="Copy order number">
            <svg class="copy-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="9" y="9" width="13" height="13" rx="2"></rect>
              <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg>
            <svg class="check-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </button></strong> has been received and is currently
        <strong><?= htmlspecialchars($status_labels[$order['status']] ?? $order['status']) ?></strong>.</p>
    </div>

    <div class="checkout-layout">
      <div class="checkout-panel">
        <h3>Shipping To</h3>
        <p class="order-confirmation-address">
          <?= htmlspecialchars($order['recipient_name']) ?><br>
          <?= htmlspecialchars($order['phone_number']) ?><br>
          <?= htmlspecialchars($order['address_line1']) ?><?= $order['address_line2'] ? ', ' . htmlspecialchars($order['address_line2']) : '' ?><br>
          <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['province']) ?> <?= htmlspecialchars($order['postal_code']) ?>
        </p>
        <h3 class="order-confirmation-section-heading">Payment Method</h3>
        <p class="order-confirmation-address"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method']))) ?></p>
      </div>

      <aside class="checkout-summary">
        <h3>Receipt</h3>
        <div class="checkout-summary-items">
          <?php foreach ($order_items as $item): ?>
            <div class="summary-item">
              <div class="summary-item-info">
                <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                <span>Qty: <?= intval($item['quantity']) ?> &times; ₱<?= number_format($item['unit_price'], 2) ?></span>
              </div>
              <div class="summary-item-subtotal">₱<?= number_format($item['subtotal'], 2) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="checkout-summary-totals">
          <div class="summary-row"><span>Subtotal</span><span>₱<?= number_format($order['subtotal'], 2) ?></span></div>
          <div class="summary-row"><span>Shipping</span><span><?= $order['shipping_fee'] > 0 ? '₱' . number_format($order['shipping_fee'], 2) : 'Free' ?></span></div>
          <div class="summary-row summary-total"><span>Total</span><span>₱<?= number_format($order['total_amount'], 2) ?></span></div>
        </div>
      </aside>
    </div>

    <div class="center-btn order-confirmation-continue-wrap">
      <a href="shop.php" class="btn btn-outline">Continue Shopping →</a>
    </div>
  <?php endif; ?>
  </main>

  <!-- SECTION: FOOTER SECTION -->
  <?php
    require_once __DIR__ . '/../components/footer.php';
  ?>

  <script>window.isLoggedIn = true;</script>
  <?php include __DIR__ . '/../includes/modals/cart-modal.php'; ?>
  <script src="../assets/js/cart.js"></script>
  <script src="../assets/js/copy-order-id.js"></script>

</body>
</html>