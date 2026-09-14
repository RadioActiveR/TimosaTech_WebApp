<?php

/* INFO: Timosa Tech — Homepage
 * http://localhost/TimosaTech/index.php)
 */

/* INFO: Linked Files:

    config/db.php
    includes/cart-functions.php

*/

session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions/cart-functions.php';
require_once __DIR__ . '/../includes/functions/site-control-functions.php';
require_once __DIR__ . '/../includes/functions/product-image-functions.php';
require_once __DIR__ . '/../helpers/icons.php';

$page_hidden = is_page_hidden($pdo, 'home');

$auth_error = $_SESSION['auth_error'] ?? null;
$auth_tab   = $_SESSION['auth_tab']   ?? null;
$auth_old_input = $_SESSION['auth_old_input'] ?? [];
unset($_SESSION['auth_error'], $_SESSION['auth_tab']);

$is_logged_in = isset($_SESSION['u_id']);
$user_name    = $_SESSION['username'] ?? '';
$is_admin     = ($_SESSION['user_role'] ?? '') === 'admin';
$cart_count   = $is_logged_in ? get_cart_count($pdo, $_SESSION['u_id']) : 0;

$page_title = "Timosa Tech - Homepage";
$current_page = 'home';

$categories = [
    'all'          => 'All Products',
    'computers'    => 'PCs & Workstations',
    'printers'     => 'Printers & Supplies',
    'networking'   => 'Networking Equipment',
    'accessories'  => 'Peripherals & Parts'
];

$nav_links = [
    ["label" => "Home",     "href" => "../index.php", "active" => true],
    ["label" => "Shop",     "href" => "shop.php"],
    ["label" => "Services", "href" => "#"],
    ["label" => "About",    "href" => "#"],
    ["label" => "Contact",  "href" => "#"],
];

$services = [
    [
        "title" => "Product Sales",
        "desc"  => "High-performance PCs, enterprise grade printers, premium networking switches and computer accessories.",
        "icon"  => "monitor",
    ],
    [
        "title" => "Printing Services",
        "desc"  => "Commercial large-format blueprint printing, high-volume production runs, and professional finishing solutions.",
        "icon"  => "printer",
    ],
    [
        "title" => "Repair & Maintenance",
        "desc"  => "Rapid diagnostics, precision board-level hardware repairs, and preventive server/workstation tune-ups.",
        "icon"  => "tool",
    ],
    [
        "title" => "IT & Network Services",
        "desc"  => "Structured cable architectures, robust local secure firewall configuration, and automated cloud backups.",
        "icon"  => "network",
    ],
];

$features = [
    [
        "title" => "Reliable Technology",
        "desc"  => "You gain access to certified enterprise hardware that scales effortlessly with your business.",
        "icon"  => "shield",
    ],
    [
        "title" => "Expert Tech Support",
        "desc"  => "Direct support from specialized engineers who diagnose and fix issues before they disrupt operations.",
        "icon"  => "headset",
    ],
    [
        "title" => "Quality Products",
        "desc"  => "Our inventory features strictly vetted components and enterprise-grade hardware.",
        "icon"  => "badge",
    ],
    [
        "title" => "Long-Term Support",
        "desc"  => "Continuous monitoring, scheduled maintenance, and a dedicated line for operational emergency support.",
        "icon"  => "infinity",
    ],
];

$stmt = $pdo->prepare("
    SELECT p.* 
    FROM products p 
    ORDER BY p.price DESC 
    LIMIT 3
");
$stmt->execute();
$products = $stmt->fetchAll();

$product_images_map = get_product_images_for_ids($pdo, array_column($products, 'product_id'));

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <meta property="og:image" content="https://laxative-daylong-spirits.ngrok-free.dev/TimosaTech/assets/images/TimosaTechLogo.png">
  
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="icon" type="image/png" href="../assets/images/TimosaTechLogo.png">
  <link rel="stylesheet" href="../assets/css/variables.css">
  <link rel="stylesheet" href="../assets/css/master.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/cart-modal.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
  <link rel="stylesheet" href="../assets/css/product-modal.css">
  <link rel="stylesheet" href="../assets/css/content-veil.css">
</head>

<body>

  <!-- INFO: NAVBAR SECTION -->
  <?php 
    require_once __DIR__ . '/../components/header.php'; 
  ?>

  <main>
  <?php if ($page_hidden): ?>
    <div class="center-container">
      <h1 class="hidden"> HIDDEN </h1>
      <h2 class="hidden-subtext"> Protocol 'CONTENT VEIL' active. Public routing disabled by Administrator. </h2>
    </div>
  <?php else: ?>
    <!-- HERO SECTION -->
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <span class="subheading">Premium Tech Services</span>
          <h1>Your Trusted <span class="highlight">Technology</span> <span class="highlight">Partner</span></h1>
          <p>Timosa Tech provides reliable hardware products, expert IT services, and
            long-term diagnostic support. We engineer stability and performance
            into your business infrastructure.</p>
          <div class="hero-buttons">
            <a href="shop.php" class="btn btn-primary">Shop Products →</a>
            <a href="contact.php" class="btn btn-secondary">Get Support</a>
          </div>
        </div>
        <div class="hero-image">
          <img class="himage" alt="server-room.png" src="../assets/images/server-room.png">
          <div class="system-monitor">
            <div class="sm-title">SYSTEM MONITOR</div>
            <div class="sm-row"><span>Uptime</span><span>99.98%</span></div>
            <div class="sm-row"><span>Service SLA</span><span>24/7/365</span></div>
          </div>
        </div>
      </div>
    </section>

    <!-- SERVICES SECTION -->
    <section class="services">
      <div class="container">
        <div class="section-header-center">
          <span class="section-tag">OUR SPECIALIZATION</span>
          <h2>Technology Solutions You Can Count On</h2>
        </div>

        <div class="services-grid">
          <?php foreach ($services as $service): ?>
            <div class="service-card">
              <div class="icon-box"><?php icon($service['icon']); ?></div>
              <h3><?= htmlspecialchars($service['title']) ?></h3>
              <p><?= htmlspecialchars($service['desc']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- FEATURED PRODUCTS SECTION -->
    <section class="featured-products">
      <div class="container">
        <div class="section-header-center">
          <span class="section-tag">OUR HARDWARE SHOWCASE</span>
          <h2>Featured Products</h2>
        </div>

        <div class="products-grid">
          <?php foreach ($products as $product): 
            $product_images = $product_images_map[$product['product_id']] ?? [];
            $image_urls = get_product_image_urls($product_images);
            $img_src = $image_urls[0];
          ?>
            <div class="product-card">
              <div class="product-thumb">
                <img src="<?= htmlspecialchars($img_src) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     style="width: 100%; height: 100%; object-fit: cover; display: block;">
              </div>
              <div class="product-body">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><?= htmlspecialchars($product['description'] ?? '') ?></p>
                <div class="product-footer">
                  <span class="product-price">₱<?= number_format($product['price'], 2) ?></span>
                  <div class="product-actions">
                    <button type="button"
                            class="quick-add-btn"
                            data-id="<?= $product['product_id'] ?>"
                            aria-label="Quick add <?= htmlspecialchars($product['name']) ?> to cart"
                            title="Add to cart">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <g transform="translate(-1,2) scale(0.72)">
                          <circle cx="9" cy="21" r="1"></circle>
                          <circle cx="20" cy="21" r="1"></circle>
                          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </g>
                        <path d="M18 2v6M15 5h6"></path>
                      </svg>
                    </button>
                    <button class="btn btn-outline view-details-btn" 
                            data-id="<?= $product['product_id'] ?>"
                            data-name="<?= htmlspecialchars($product['name']) ?>"
                            data-price="₱<?= number_format($product['price'], 2) ?>"
                            data-stock="<?= intval($product['stock']) ?>"
                            data-category="<?= htmlspecialchars($categories[$product['category']] ?? $product['category']) ?>"
                            data-desc="<?= htmlspecialchars($product['description'] ?? 'No detailed description available.') ?>"
                            data-img="<?= htmlspecialchars($img_src) ?>"
                            data-images="<?= htmlspecialchars(json_encode($image_urls)) ?>">
                      View Details
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="center-btn">
          <a href="shop.php" class="btn btn-outline">View All Products →</a>
        </div>
      </div>
    </section>

    <!-- WHY CHOOSE US SECTION -->
    <section class="why-choose-us">
      <div class="container">
        <div class="section-header-center">
          <span class="section-tag">THE TIMOSA DIFFERENCE</span>
          <h2>Why Leading Businesses Choose Us</h2>
        </div>

        <div class="features-grid">
          <?php foreach ($features as $feature): ?>
            <div class="feature-card">
              <div class="icon-box"><?php icon($feature['icon']); ?></div>
              <h3><?= htmlspecialchars($feature['title']) ?></h3>
              <p><?= htmlspecialchars($feature['desc']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- CALL TO ACTION SECTION -->
    <section class="cta-banner">
      <div class="container">
        <h2>Need Technical Support?</h2>
        <p>Whether you have system errors, hardware failure, or need custom deployment, our certified technical helpdesk is standing by. Get instant relief.</p>
        <a href="contact.php" class="btn-cta">
          REQUEST SUPPORT NOW
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="4" width="16" height="12" rx="2"></rect>
            <path d="M9 20l3-4h8"></path>
          </svg>
        </a>
      </div>
    </section>
  <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../includes/modals/product-modal.php'; ?>

  <!-- INFO: FOOTER SECTION -->
  <?php 
    require_once __DIR__ . '/../components/footer.php'; 
  ?>

  <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
  <script src="../assets/js/product-modal.js"></script>
  <?php include __DIR__ . '/../includes/modals/login-signup-modal.php'; ?>
  <?php include __DIR__ . '/../includes/modals/cart-modal.php'; ?>
  <script src="../assets/js/auth.js"></script>
  <script src="../assets/js/cart.js"></script>

</body>
</html>