<?php
// Determine component view modes based on $current_page
$is_admin_page   = (isset($current_page) && $current_page === 'admin');
$is_profile_page = (isset($current_page) && $current_page === 'profile');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title ?? 'Timosa Tech') ?></title>
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/master.css">
  <link rel="stylesheet" href="../styles/styles.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
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
        <nav class="nav-links">
          <?php if ($is_profile_page): ?>
            <!-- Only show Home link when on the Profile page -->
            <a href="/TimosaTech/pages/homepage.php">Home</a>
          <?php else: ?>
            <!-- Full Navigation for Standard Pages -->
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
          <?php endif; ?>
        </nav>
      <?php endif; ?>

      <!-- USER ACTIONS & CTA SECTION -->
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
              Cart <span class="cart-count-badge" style="<?= ($cart_count ?? 0) === 0 ? 'display:none;' : '' ?>"><?= $cart_count ?? 0 ?></span>
            </button>
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

    </div>
  </header>