<?php

/* INFO: Linked Files:

    config/db.php

*/

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions/site-control-functions.php';

if (!isset($_SESSION['u_id'])) {
    header("Location: homepage.php");
    exit();
}

$u_id      = $_SESSION['u_id'];
$page_hidden = is_page_hidden($pdo, 'order_confirmation');
$user_name = $_SESSION['username'] ?? '';
$is_admin  = ($_SESSION['user_role'] ?? '') === 'admin';
$order_id  = $_GET['order_id'] ?? '';

// Scoped to u_id so a user can't view someone else's order by guessing the order_id
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

$page_title = "Timosa Tech - Order Confirmation";

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
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/page-veil.css">
</head>
<body>

  <header class="navbar">
    <div class="container">
      <div class="logo">
        <img class="img-logo" src="../assets/images/TimosaTechLogo.png" alt="Logo">
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
          <a href="../admin/admin-portal.php" class="btn btn-outline admin-nav-btn">Admin Portal</a>
        <?php endif; ?>
        <a href="../includes/handlers/logout-handler.php" class="btn btn-outline">Log Out</a>
      </div>
    </div>
  </header>

  <main class="container checkout-main">
  <?php if ($page_hidden): ?>
    <h1 class="hidden"> HIDDEN </h1>
  <?php else: ?>
    <div class="order-confirm-banner">
      <span class="section-tag">ORDER PLACED</span>
      <h1>Thank you, <?= htmlspecialchars(explode(' ', $order['recipient_name'])[0]) ?>!</h1>
      <p>Your order <strong>#<?= htmlspecialchars($order['order_id']) ?></strong> has been received and is currently
        <strong><?= htmlspecialchars($status_labels[$order['status']] ?? $order['status']) ?></strong>.</p>
    </div>

    <div class="checkout-layout">
      <section class="checkout-panel" style="flex: 1 1 55%;">
        <h3>Shipping To</h3>
        <p style="color: var(--text-muted); line-height: 1.7;">
          <?= htmlspecialchars($order['recipient_name']) ?><br>
          <?= htmlspecialchars($order['phone_number']) ?><br>
          <?= htmlspecialchars($order['address_line1']) ?><?= $order['address_line2'] ? ', ' . htmlspecialchars($order['address_line2']) : '' ?><br>
          <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['province']) ?> <?= htmlspecialchars($order['postal_code']) ?>
        </p>
        <h3 style="margin-top: 20px;">Payment Method</h3>
        <p style="color: var(--text-muted);"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method']))) ?></p>
      </section>

      <aside class="checkout-summary">
        <h3>Receipt</h3>
        <div class="checkout-summary-items">
          <?php foreach ($order_items as $item): ?>
            <div class="summary-item">
              <div class="summary-item-info">
                <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                <span>Qty: <?= intval($item['quantity']) ?> &times; $<?= number_format($item['unit_price'], 2) ?></span>
              </div>
              <div class="summary-item-subtotal">₱<?= number_format($item['subtotal'], 2) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="checkout-summary-totals">
          <div class="summary-row"><span>Subtotal</span><span>₱<?= number_format($order['subtotal'], 2) ?></span></div>
          <div class="summary-row"><span>Shipping</span><span><?= $order['shipping_fee'] > 0 ? '$' . number_format($order['shipping_fee'], 2) : 'Free' ?></span></div>
          <div class="summary-row summary-total"><span>Total</span><span>₱<?= number_format($order['total_amount'], 2) ?></span></div>
        </div>
      </aside>
    </div>

    <div class="center-btn" style="margin-top: 30px;">
      <a href="shop.php" class="btn btn-outline">Continue Shopping →</a>
    </div>
  <?php endif; ?>
  </main>

</body>
</html>