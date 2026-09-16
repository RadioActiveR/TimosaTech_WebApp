<?php
require_once __DIR__ . '/../helpers/icons.php';
require_once __DIR__ . '/../includes/functions/notification-functions.php';

// Determine component view modes based on $current_page
$is_admin_page     = (isset($current_page) && $current_page === 'admin');
$is_profile_page   = (isset($current_page) && $current_page === 'profile');
$is_minimal_header = (isset($current_page) && in_array($current_page, ['order_confirmation', 'checkout'], true));

// Notification bell: which feed to show (customer vs admin) is purely a
// function of which page we're on and whether someone's logged in — kept
// self-contained here (like is_page_hidden() elsewhere) so no other page
// needs to be touched to get this feature.
$notif_role = null;
$notif_initial_unread = 0;
if (isset($pdo) && isset($_SESSION['u_id'])) {
    if ($is_admin_page && ($_SESSION['user_role'] ?? '') === 'admin') {
        $notif_role = 'admin';
        $notif_initial_unread = get_unread_count_for_admin($pdo);
    } elseif (!$is_admin_page) {
        $notif_role = 'user';
        $notif_initial_unread = get_unread_count_for_user($pdo, $_SESSION['u_id']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title ?? 'Timosa Tech') ?></title>
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/master.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
  <link rel="stylesheet" href="../assets/css/chat-widget.css">
  <link rel="stylesheet" href="../assets/css/notifications.css">
</head>
<body>

  <!-- NAVBAR -->
  <header class="navbar <?= $is_admin_page ? 'admin-navbar' : '' ?>">
    <div class="container <?= $is_admin_page ? 'admin-nav-container' : '' ?>">
      
      <?php if ($is_admin_page): ?>
        <!-- ADMIN BRANDING -->
        <div class="admin-brand">
          <a href="/TimosaTech/pages/homepage.php" class="logo">
            <img class="img-logo" src="/TimosaTech/assets/images/TimosaTechLogo.png" alt="Logo" onerror="this.style.display='none'">
            <span class="logoname1">TIMOSA</span><span class="logoname2">TECH</span>
          </a>
          <span class="admin-badge">ADMINISTRATOR PORTAL</span>
        </div>
      <?php else: ?>
        <!-- STANDARD BRANDING -->
        <div class="logo">
          <img class="img-logo" src="/TimosaTech/assets/images/TimosaTechLogo.png" alt="Timosa Tech Logo" onerror="this.style.display='none'">
          <a class="logoname1" href="/TimosaTech/index.php">TIMOSA</a><a class="logoname2" href="/TimosaTech/index.php">TECH</a>
        </div>

        <!-- NAVIGATION LINKS -->
        <?php if (!$is_minimal_header && !$is_profile_page): ?>
        <nav class="nav-links">
          <?php
            $nav_items = [
                'home'     => ['label' => 'Home',     'href' => '/TimosaTech/pages/homepage.php'],
                'shop'     => ['label' => 'Shop',     'href' => '/TimosaTech/pages/shop.php'],
                'services' => ['label' => 'Services', 'href' => '/TimosaTech/pages/services.php'],
                'about'    => ['label' => 'About',    'href' => '/TimosaTech/pages/about.php'],
                'contact'  => ['label' => 'Contact',  'href' => '/TimosaTech/pages/contact.php'],
            ];

            foreach ($nav_items as $key => $item): 
                $active_class = (isset($current_page) && $current_page === $key) ? 'class="active"' : '';
            ?>
                <a href="<?= htmlspecialchars($item['href']) ?>" <?= $active_class ?>>
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
      <?php endif; ?>

      <!-- USER ACTIONS & CTA SECTION -->
      <?php if (!$is_minimal_header): ?>
      <div class="nav-cta">
        <?php if (!empty($is_logged_in) || $is_admin_page || $is_profile_page): ?>
          
          <span class="nav-greeting">Hi, <?= htmlspecialchars(
              mb_strlen($user_name ?? '') > 13 
                  ? explode(' ', trim($user_name ?? 'User'))[0] 
                  : ($user_name ?? 'User')
          ) ?></span>

          <?php if ($is_admin_page): ?>
            <!-- Admin View Actions -->
            <a href="/TimosaTech/pages/homepage.php" class="btn btn-outline admin-nav-btn">View Main Site</a>
          <?php else: ?>
            <!-- Standard View Admin Button (Hidden on Profile Page) -->
            <?php if (!empty($is_admin) && !$is_profile_page): ?>
              <a href="/TimosaTech/admin/admin-portal.php" class="btn btn-outline admin-nav-btn">Admin Portal</a>
            <?php endif; ?>

            <!-- Cart Button (Visible on both Standard & Profile Pages) -->
            <button type="button" class="btn btn-outline cart-nav-btn" data-open-cart>
              <?php icon('cart'); ?><span class="cart-count-badge" style="<?= ($cart_count ?? 0) === 0 ? 'display:none;' : '' ?>"><?= $cart_count ?? 0 ?></span>
            </button>
          <?php endif; ?>

          <?php if ($notif_role): ?>
            <!-- Notification Bell (Customers get chat replies + order
                 updates; Admins get new orders + conversations needing
                 attention) -->
            <div class="notif-widget" id="notifWidget">
              <button type="button" class="btn btn-outline notif-bell-btn" id="notifBellBtn"
                      aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                  <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="notif-count-badge" id="notifCountBadge" style="<?= $notif_initial_unread === 0 ? 'display:none;' : '' ?>"><?= $notif_initial_unread > 99 ? '99+' : $notif_initial_unread ?></span>
              </button>
              <div class="notif-panel" id="notifPanel">
                <div class="notif-panel-header">
                  <span>Notifications</span>
                  <button type="button" class="notif-mark-all-btn" id="notifMarkAllBtn">Mark all read</button>
                </div>
                <div class="notif-list" id="notifList">
                  <p class="notif-empty">Loading…</p>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($is_profile_page): ?>
            <!-- Logout Action (Profile Page Exclusive) -->
            <a href="/TimosaTech/includes/handlers/logout-handler.php" class="btn btn-outline logout-btn">Logout</a>
          <?php else: ?>
            <!-- Circular Profile Button (Hidden on Profile Page) -->
            <a href="/TimosaTech/pages/profile.php" class="btn btn-outline profile-circle-btn" title="My Profile" aria-label="My Profile">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </a>
          <?php endif; ?>

        <?php else: ?>
          <a href="#" class="btn btn-outline" data-open-auth="login">Log In</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>
  </header>

  <?php if ($notif_role): ?>
    <script>
      window.notifRole = <?= json_encode($notif_role) ?>;
      // Absolute path so it resolves correctly regardless of which
      // directory depth the current page lives at (pages/ vs admin/).
      window.notifEndpoint = <?= $notif_role === 'admin'
          ? json_encode('/TimosaTech/admin/includes/handlers/admin-notification-handler.php')
          : json_encode('/TimosaTech/includes/handlers/notification-handler.php') ?>;
    </script>
    <script src="../assets/js/notifications.js"></script>
  <?php endif; ?>

  <?php if (!$is_admin_page && (!isset($current_page) || $current_page !== 'contact')): ?>
    <?php require_once __DIR__ . '/../includes/widgets/chat-widget.php'; ?>
    <script src="../assets/js/chat-widget.js"></script>
  <?php endif; ?>