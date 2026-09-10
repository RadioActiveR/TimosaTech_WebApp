<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/user-profile-functions.php';

// Redirect to home if not logged in
if (!isset($_SESSION['u_id'])) {
    header('Location: index.php');
    exit;
}

$u_id = $_SESSION['u_id'];
$success_msg = '';
$error_msg = '';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
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
        $success_msg = "Profile updated successfully!";
    } else {
        $error_msg = "Failed to update profile. Please try again.";
    }
}

// Fetch current user data
$profile = get_user_profile($pdo, $u_id);
$orders = get_user_orders($pdo, $u_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile — Timosa Tech</title>
  <link rel="stylesheet" href="../styles/styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

  <!-- NAVBAR -->
  <header class="navbar">
    <div class="container">
      <a href="../index.php" class="logo">
        <img src="../assets/logo.png" alt="Timosa Tech Logo" class="img-logo" onerror="this.style.display='none'">
        <span class="logoname1">TIMOSA</span><span class="logoname2">TECH</span>
      </a>

      <nav class="nav-links">
        <a href="../index.php">Home</a>
        <a href="shop.php">Shop</a>
        <a href="../index.php#services">Services</a>
        <a href="profile.php" class="active">My Account</a>
      </nav>

      <div class="nav-cta">
        <a href="cart.php" class="btn btn-secondary cart-nav-btn">
          <span>Cart</span>
          <span class="cart-count-badge" id="cartCount">0</span>
        </a>
        <a href="../actions/logout.php" class="btn btn-outline">Logout</a>
      </div>
    </div>
  </header>

  <!-- MAIN CONTENT -->
  <main class="profile-main">
    <div class="container">
      
      <div class="profile-header-banner">
        <span class="section-tag">ACCOUNT CONTROL PANEL</span>
        <h1>User <span class="highlight">Profile</span></h1>
        <p class="profile-subtitle">Manage your personal information, address details, and track your recent orders.</p>
      </div>

      <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
      <?php endif; ?>

      <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
      <?php endif; ?>

      <div class="profile-grid">
        
        <!-- SIDEBAR: USER INFO -->
        <aside class="profile-sidebar">
          <div class="profile-card">
            <div class="profile-avatar-circle">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <h3 class="profile-user-name"><?= htmlspecialchars($profile['full_name'] ?? $profile['username']) ?></h3>
            <p class="profile-user-email"><?= htmlspecialchars($profile['email']) ?></p>

            <div class="profile-info-list">
              <div class="profile-info-item">
                <span class="profile-info-label">Username</span>
                <span class="profile-info-value">@<?= htmlspecialchars($profile['username']) ?></span>
              </div>
              <div class="profile-info-item">
                <span class="profile-info-label">Role</span>
                <span class="profile-info-value badge-role"><?= htmlspecialchars(strtoupper($profile['role'])) ?></span>
              </div>
              <div class="profile-info-item">
                <span class="profile-info-label">Member Since</span>
                <span class="profile-info-value"><?= date('M Y', strtotime($profile['account_created'])) ?></span>
              </div>
            </div>
          </div>
        </aside>

        <!-- MAIN AREA: FORM & ORDERS -->
        <div class="profile-content-area">
          
          <!-- PERSONAL INFORMATION FORM -->
          <section class="profile-section-card">
            <h2>Personal Information</h2>
            <form action="profile.php" method="POST" class="profile-form">
              <input type="hidden" name="action" value="update_profile">

              <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" placeholder="John Doe">
              </div>

              <div class="form-grid-2">
                <div class="form-group">
                  <label for="phone_number">Phone Number</label>
                  <input type="text" id="phone_number" name="phone_number" class="form-control" value="<?= htmlspecialchars($profile['phone_number'] ?? '') ?>" placeholder="+63 900 000 0000">
                </div>
                <div class="form-group">
                  <label for="city">City</label>
                  <input type="text" id="city" name="city" class="form-control" value="<?= htmlspecialchars($profile['city'] ?? '') ?>" placeholder="City">
                </div>
              </div>

              <div class="form-group">
                <label for="address_line1">Address Line 1</label>
                <input type="text" id="address_line1" name="address_line1" class="form-control" value="<?= htmlspecialchars($profile['address_line1'] ?? '') ?>" placeholder="Street address, building, suite">
              </div>

              <div class="form-group">
                <label for="address_line2">Address Line 2 (Optional)</label>
                <input type="text" id="address_line2" name="address_line2" class="form-control" value="<?= htmlspecialchars($profile['address_line2'] ?? '') ?>" placeholder="Apartment, floor, landmark">
              </div>

              <div class="form-grid-2">
                <div class="form-group">
                  <label for="province">Province / Region</label>
                  <input type="text" id="province" name="province" class="form-control" value="<?= htmlspecialchars($profile['province'] ?? '') ?>" placeholder="Province">
                </div>
                <div class="form-group">
                  <label for="postal_code">Postal Code</label>
                  <input type="text" id="postal_code" name="postal_code" class="form-control" value="<?= htmlspecialchars($profile['postal_code'] ?? '') ?>" placeholder="7100">
                </div>
              </div>

              <button type="submit" class="btn btn-primary">Save Profile Changes</button>
            </form>
          </section>

          <!-- ORDER HISTORY -->
          <section class="profile-section-card">
            <h2>Order History</h2>
            <?php if (empty($orders)): ?>
              <p class="profile-no-orders">No orders placed yet. <a href="shop.php">Browse our store</a>.</p>
            <?php else: ?>
              <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                  <?php $items = get_user_order_items($pdo, $order['order_id'], $u_id); ?>
                  <div class="order-card">
                    <div class="order-header">
                      <div>
                        <span class="order-id">#<?= htmlspecialchars($order['order_id']) ?></span>
                        <span class="order-date"><?= date('M d, Y', strtotime($order['created_at'])) ?></span>
                      </div>
                      <span class="order-status-badge status-<?= strtolower($order['status']) ?>">
                        <?= htmlspecialchars($order['status']) ?>
                      </span>
                    </div>

                    <div class="order-body">
                      <table class="order-items-table">
                        <thead>
                          <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Price</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($items as $item): ?>
                            <tr>
                              <td><?= htmlspecialchars($item['product_name']) ?></td>
                              <td><?= (int)$item['quantity'] ?></td>
                              <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>

                      <div class="order-footer">
                        <span>Total Paid</span>
                        <span class="order-total-amount">₱<?= number_format($order['total_amount'], 2) ?></span>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>

        </div>
      </div>
    </div>
  </main>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col">
          <div class="logo">
            <span class="logoname1">TIMOSA</span><span class="logoname2">TECH</span>
          </div>
          <p>Your ultimate destination for premium technology and components.</p>
        </div>
        <div class="footer-col">
          <h4>Navigation</h4>
          <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="shop.php">Shop</a></li>
            <li><a href="profile.php">My Account</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Support</h4>
          <ul>
            <li><a href="#">Contact Us</a></li>
            <li><a href="#">FAQs</a></li>
            <li><a href="#">Shipping Policy</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Connect</h4>
          <div class="social-links">
            <a href="#">FB</a>
            <a href="#">X</a>
            <a href="#">IG</a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Timosa Tech. All rights reserved.</p>
      </div>
    </div>
  </footer>

</body>
</html>