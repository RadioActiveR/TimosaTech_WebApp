<?php

/* INFO: Linked Files:

    config/db.php
    includes/functions/user-profile-functions.php
    includes/handlers/profile-handler.php

*/

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions/user-profile-functions.php';
require_once __DIR__ . '/../includes/functions/site-control-functions.php';
require_once __DIR__ . '/../includes/functions/cart-functions.php';
require_once __DIR__ . '/../helpers/icons.php';

if (!isset($_SESSION['u_id'])) {
    header('Location: ../index.php');
    exit;
}

$is_logged_in = isset($_SESSION['u_id']);
$cart_count   = $is_logged_in ? get_cart_count($pdo, $_SESSION['u_id']) : 0;
$user_name    = $_SESSION['username'] ?? '';

$u_id = $_SESSION['u_id'];
$page_hidden = is_page_hidden($pdo, 'profile');

$page_title = "Timosa Tech - Profile";
$current_page = 'profile';

$success_msg          = $_SESSION['profile_success'] ?? '';
$error_msg             = $_SESSION['profile_error'] ?? '';
$username_success_msg = $_SESSION['username_success'] ?? '';
$username_error_msg   = $_SESSION['username_error'] ?? '';

unset(
    $_SESSION['profile_success'],
    $_SESSION['profile_error'],
    $_SESSION['username_success'],
    $_SESSION['username_error']
);

$profile = get_user_profile($pdo, $u_id);
$orders = get_user_orders($pdo, $u_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile — Timosa Tech</title>
  <link rel="icon" type="image/png" href="../assets/images/TimosaTechLogo.png">
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/master.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/cart-modal.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700;800&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/content-veil.css">
</head>
<body>

  <!-- SECTION: NAVBAR -->
  <?php 
    require_once __DIR__ . '/../components/header.php'; 
  ?>

  <!-- SECTION: MAIN CONTENT -->
  <main class="profile-main">
  <?php if ($page_hidden): ?>
    <div class="center-container">
      <h1 class="hidden"> HIDDEN </h1>
      <h2 class="hidden-subtext"> Protocol 'CONTENT VEIL' active. Public routing disabled by Administrator. </h2>
    </div>
  <?php else: ?>
    <div class="container">
      
      <div class="profile-header-banner">
        <div class="profile-header-row">
          <div>
            <span class="section-tag">ACCOUNT CONTROL PANEL</span>
            <h1>User <span class="highlight">Profile</span></h1>
          </div>
          <button type="button" class="back-btn" id="profileBackBtn">&larr; Back</button>
        </div>
        <p class="profile-subtitle">Manage your personal information, address details, and track your recent orders.</p>
      </div>

      <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
      <?php endif; ?>

      <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
      <?php endif; ?>

      <div class="profile-grid">
        
        <!-- SECTION: SIDEBAR: USER INFO -->
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

        <!-- SECTION: MAIN AREA: FORM & ORDERS -->
        <div class="profile-content-area">
          
          <!-- SECTION: PERSONAL INFORMATION FORM -->
          <section class="profile-section-card">
            <h2>Personal Information</h2>
            <form action="../includes/handlers/profile-handler.php" method="POST" class="profile-form">
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

          <!-- SECTION: USERNAME -->
          <section class="profile-section-card">
            <h2>Change Username</h2>

            <?php if ($username_success_msg): ?>
              <div class="alert alert-success"><?= htmlspecialchars($username_success_msg) ?></div>
            <?php endif; ?>

            <?php if ($username_error_msg): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($username_error_msg) ?></div>
            <?php endif; ?>

            <form action="../includes/handlers/profile-handler.php" method="POST" class="profile-form">
              <input type="hidden" name="action" value="update_username">

              <div class="form-group">
                <label for="new_username">Username</label>
                <input type="text" id="new_username" name="new_username" class="form-control"
                       value="<?= htmlspecialchars($profile['username']) ?>"
                       required minlength="3" maxlength="20" pattern="[A-Za-z][A-Za-z0-9_]*"
                       title="Must start with a letter, then only letters, numbers, and underscores">
              </div>

              <button type="submit" class="btn btn-outline">Update Username</button>
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
                        <span class="order-id-copy-wrap">
                          <span class="order-id">#<?= htmlspecialchars($order['order_id']) ?></span>
                          <button type="button" class="copy-order-id-btn" data-copy="<?= htmlspecialchars($order['order_id']) ?>" aria-label="Copy order number">
                            <svg class="copy-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                              <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            <svg class="check-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                          </button>
                        </span>
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
  <?php endif; ?>
  </main>

  <!-- SECTION: FOOTER SECTION -->
  <?php 
    require_once __DIR__ . '/../components/footer.php'; 
  ?>

  <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
  <script>
    (function () {
      var key = 'profileEntryReferrer';
      var ref = document.referrer;
      var cameFromProfileFlow = ref && (
        ref.indexOf('/pages/profile.php') !== -1 ||
        ref.indexOf('/includes/handlers/profile-handler.php') !== -1
      );

      if (!cameFromProfileFlow) {
        if (ref && ref.indexOf(window.location.origin) === 0) {
          sessionStorage.setItem(key, ref);
        } else {
          sessionStorage.removeItem(key);
        }
      }
    })();

    document.getElementById('profileBackBtn')?.addEventListener('click', function () {
      var storedRef = sessionStorage.getItem('profileEntryReferrer');
      if (storedRef) {
        window.location.href = storedRef;
      } else {
        window.location.href = 'homepage.php';
      }
    });
  </script>
  <?php include __DIR__ . '/../includes/modals/cart-modal.php'; ?>
  <script src="../assets/js/cart.js"></script>
  <script src="../assets/js/copy-order-id.js"></script>

</body>
</html>