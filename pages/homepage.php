<?php

/* INFO: Timosa Tech — Homepage
 * http://localhost/TimosaTech/index.php)
 */

session_start();

require_once __DIR__ . '/../includes/db.php';

$auth_error = $_SESSION['auth_error'] ?? null;
$auth_tab   = $_SESSION['auth_tab']   ?? null;
$auth_old_input = $_SESSION['auth_old_input'] ?? [];
unset($_SESSION['auth_error'], $_SESSION['auth_tab']);

$is_logged_in = isset($_SESSION['u_id']);
$user_name    = $_SESSION['username'] ?? '';
$is_admin     = ($_SESSION['user_role'] ?? '') === 'admin';

$page_title = "Timosa Tech - Homepage";

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

$footer_links = [
    "Quick Links" => [
        ["Home", "../index.php"],
        ["Shop Hardware", "shop.php"],
        ["About Us", "#"],
        ["Contact Us", "#"],
        ["Privacy Policy", "#"],
    ],
    "Services" => [
        ["Product Sales", "#"],
        ["Printing Services", "#"],
        ["Diagnostic Repairs", "#"],
        ["Managed IT Solutions", "#"],
    ],
];

function icon(string $name): void {
    $icons = [
        "monitor"  => '<path d="M4 5h16v10H4z"/><path d="M9 19h6M12 15v4"/>',
        "printer"  => '<path d="M6 9V4h12v5"/><rect x="4" y="9" width="16" height="7" rx="1"/><path d="M6 16h12v4H6z"/>',
        "tool"     => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2-2 2.5-2.5z"/>',
        "network"  => '<circle cx="12" cy="5" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/><path d="M12 7v6M12 13 6 17M12 13l6 4"/>',
        "shield"   => '<path d="M12 3l7 3v6c0 4.4-3 7.9-7 9-4-1.1-7-4.6-7-9V6z"/>',
        "headset"  => '<path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/>',
        "badge"    => '<circle cx="12" cy="9" r="5"/><path d="M9 13.5 7.5 21 12 18.5 16.5 21 15 13.5"/>',
        "infinity" => '<path d="M7 15a3.5 3.5 0 1 1 0-7c2.5 0 3.5 3.5 5 3.5s2.5-3.5 5-3.5a3.5 3.5 0 1 1 0 7c-2.5 0-3.5-3.5-5-3.5s-2.5 3.5-5 3.5z"/>',
        "tower"    => '<rect x="7" y="3" width="10" height="18" rx="1.5"/><path d="M10 7h4M10 11h4M10 15h1.5"/>',
        "router"   => '<rect x="3" y="10" width="18" height="7" rx="1.5"/><path d="M7 10V7a2 2 0 0 1 2-2M17 10V7a2 2 0 0 0-2-2M7 17v2M11 17v2M15 17v2"/>',

        "facebook"  => '<circle cx="12" cy="12" r="11" fill="#0D192B" stroke="none"/><path d="M13.5 12h1.25l.5-2.5h-1.75V8.25c0-.655 0-1.25 1.25-1.25h.75V4.845c-.163-.022-.802-.095-1.475-.095-1.48 0-2.522.903-2.522 2.565V9.5H10v2.5h1.515V18h2v-6z" fill="#00E5FF" stroke="none"/>',
        "twitter"   => '<circle cx="12" cy="12" r="11" fill="#0D192B" stroke="none"/><path d="M17.33 8.26a4.8 4.8 0 0 1-1.41.39 2.5 2.5 0 0 0 1.08-1.36 5 5 0 0 1-1.7.62 2.5 2.5 0 0 0-4.25 2.28 7.08 7.08 0 0 1-5.16-2.61 2.5 2.5 0 0 0 .77 3.33 2.46 2.46 0 0 1-1.13-.31v.03a2.5 2.5 0 0 0 2 2.45 2.5 2.5 0 0 1-1.13.04 2.5 2.5 0 0 0 2.33 1.74 5.02 5.02 0 0 1-3.7 1.04 7.07 7.07 0 0 0 3.83 1.12c4.6 0 7.12-3.81 7.12-7.12v-.32a5.1 5.1 0 0 0 1.25-1.29z" fill="#00E5FF" stroke="none"/>',
        "linkedin"  => '<circle cx="12" cy="12" r="11" fill="#0D192B" stroke="none"/><path d="M8.33 7.33a1 1 0 1 0 0 2 1 1 0 0 0 0-2zM7.5 10.33h1.67V16.67H7.5V10.33zm4 0h1.6v.87h.03a1.76 1.76 0 0 1 1.57-.87c1.68 0 1.97 1.1 1.97 2.54v3.8H15.03v-3.35c0-.8-.01-1.83-1.1-1.83-1.1 0-1.28.87-1.28 1.77v3.41H11.5v-6.34z" fill="#00E5FF" stroke="none"/>',
        "instagram" => '<circle cx="12" cy="12" r="11" fill="#0D192B" stroke="none"/><rect x="6.67" y="6.67" width="10.66" height="10.66" rx="3" stroke="#00E5FF" stroke-width="1.3" fill="none"/><circle cx="12" cy="12" r="2.67" stroke="#00E5FF" stroke-width="1.3" fill="none"/><circle cx="15" cy="9" r="0.67" fill="#00E5FF" stroke="none"/>',
    ];
    echo '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">'
        . ($icons[$name] ?? $icons["monitor"]) . '</svg>';
}

$stmt = $pdo->prepare("
    SELECT p.*, i.image_data, i.mime_type 
    FROM products p 
    LEFT JOIN images i ON p.image_id = i.image_id 
    ORDER BY p.price DESC 
    LIMIT 3
");
$stmt->execute();
$products = $stmt->fetchAll();

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

  <!-- NAVBAR SECTION -->
  <header class="navbar">
    <div class="container">
      <div class="logo">
        <img class="img-logo" src="../images/TimosaTechLogo.png">
        <a class="logoname1">TIMOSA</a><a class="logoname2">TECH</a>
      </div>
      <nav class="nav-links">
        <?php foreach ($nav_links as $link): ?>
          <a href="<?= htmlspecialchars($link['href']) ?>" class="<?= !empty($link['active']) ? 'active' : '' ?>">
            <?= htmlspecialchars($link['label']) ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="nav-cta">
        <?php if ($is_logged_in): ?>
          <span class="nav-greeting">Hi, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?></span>
    
          <?php if ($is_admin): ?>
            <a href="admin-dashboard.php" class="btn btn-outline admin-nav-btn">Admin Dashboard</a>
          <?php endif; ?>
    
          <a href="../includes/logout.php" class="btn btn-outline">Log Out</a>
        <?php else: ?>
          <a href="#" class="btn btn-outline" data-open-auth="login">Sign Up / Log In</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main>
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
            <a href="#" class="btn btn-secondary">Get Support</a>
          </div>
        </div>
        <div class="hero-image">
          <img class="himage" alt="server-room.png" src="../images/server-room.png">
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
            $img_src = !empty($product['image_data']) 
              ? 'data:' . htmlspecialchars($product['mime_type'] ?? 'image/png') . ';base64,' . $product['image_data'] 
              : '../images/workstation-rig.png';
          ?>
            <div class="product-card">
              <div class="product-thumb">
                <img src="<?= $img_src ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     style="width: 100%; height: 100%; object-fit: cover; display: block;">
              </div>
              <div class="product-body">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><?= htmlspecialchars($product['description'] ?? '') ?></p>
                <div class="product-footer">
                  <span class="product-price">$<?= number_format($product['price'], 2) ?></span>
                  <button class="btn btn-outline view-details-btn" 
                          data-name="<?= htmlspecialchars($product['name']) ?>"
                          data-price="$<?= number_format($product['price'], 2) ?>"
                          data-stock="<?= intval($product['stock']) ?>"
                          data-category="<?= htmlspecialchars($categories[$product['category']] ?? $product['category']) ?>"
                          data-desc="<?= htmlspecialchars($product['description'] ?? 'No detailed description available.') ?>"
                          data-img="<?= $img_src ?>">
                    View Details
                  </button>
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
        <a href="#" class="btn-cta">
          REQUEST SUPPORT NOW
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="4" width="16" height="12" rx="2"></rect>
            <path d="M9 20l3-4h8"></path>
          </svg>
        </a>
      </div>
    </section>
  </main>

  <!-- PRODUCT DETAILS MODAL -->
  <div class="modal-overlay" id="productModal">
    <div class="product-modal">
      <button class="modal-close" id="closeModal">&times;</button>
      <div class="modal-image-container">
        <img id="modalImg" src="" alt="Product Image">
      </div>
      <div class="modal-details">
        <h2 id="modalTitle">Product Title</h2>
        <div class="modal-meta">
          <span>Category: <strong id="modalCategory" style="color:#fff;">-</strong></span>
          <span>In Stock: <strong id="modalStock" style="color:#fff;">-</strong></span>
        </div>
        <p class="modal-desc" id="modalDesc"></p>
        <div class="modal-footer">
          <span class="modal-price" id="modalPrice">$0.00</span>
          <button class="btn btn-primary">Add to Cart</button>
        </div>
      </div>
    </div>
  </div>

  <!-- FOOTER SECTION -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col brand-col">
          <div class="logo">
            <img class="img-logo" src="../images/TimosaTechLogo.png">
            <a class="logoname1">TIMOSA</a><a class="logoname2">TECH</a>
          </div>
          <p>Premium enterprise technology, high-quality printing solutions, computer hardware, and diagnostics support.</p>
          <div class="social-links">
            <a href="https://web.facebook.com/people/Timosa-Tech/61590409082212/" aria-label="Facebook"><?php icon('facebook'); ?></a>
            <a href="https://x.com" aria-label="Twitter"><?php icon('twitter'); ?></a>
            <a href="https://www.instagram.com" aria-label="Instagram"><?php icon('instagram'); ?></a>
            <a href="https://www.linkedin.com" aria-label="LinkedIn"><?php icon('linkedin'); ?></a>
          </div>
        </div>

        <?php foreach ($footer_links as $heading => $links): ?>
          <div class="footer-col">
            <h4><?= htmlspecialchars($heading) ?></h4>
            <ul>
              <?php foreach ($links as [$label, $href]): ?>
                <li><a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>

        <div class="footer-col">
          <h4>Contact Info</h4>
          <p>101 Tech Junction, Suite A</p>
          <p>support@timosatech.com</p>
          <p>+1 (555) 019-2831</p>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; <?= date("Y") ?> TimosaTech. All rights reserved. All specifications subject to technical review.</p>
      </div>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const modal = document.getElementById('productModal');
      const closeModal = document.getElementById('closeModal');

      document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', (e) => {
          e.preventDefault();
          document.getElementById('modalTitle').textContent = button.dataset.name;
          document.getElementById('modalPrice').textContent = button.dataset.price;
          document.getElementById('modalCategory').textContent = button.dataset.category;
          document.getElementById('modalStock').textContent = button.dataset.stock;
          document.getElementById('modalDesc').textContent = button.dataset.desc;
          document.getElementById('modalImg').src = button.dataset.img;
          modal.classList.add('active');
        });
      });

      if (closeModal) {
        closeModal.addEventListener('click', () => modal.classList.remove('active'));
      }

      if (modal) {
        modal.addEventListener('click', (e) => {
          if (e.target === modal) modal.classList.remove('active');
        });
      }
    });
  </script>

  <?php include __DIR__ . '/../includes/auth-modal.php'; ?>
  <script src="../js/auth.js"></script>

</body>
</html>