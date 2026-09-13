<?php

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

$is_logged_in = isset($_SESSION['u_id']);
$user_name    = $_SESSION['username'] ?? '';
$is_admin     = ($_SESSION['user_role'] ?? '') === 'admin';
$cart_count   = $is_logged_in ? get_cart_count($pdo, $_SESSION['u_id']) : 0;
$page_hidden  = is_page_hidden($pdo, 'shop');

$page_title = "Timosa Tech - Store";
$current_page = 'shop';

// Category Filter Logic
$selected_category = $_GET['category'] ?? 'all';
$search_query      = trim($_GET['search'] ?? '');

$sql = "SELECT p.* FROM products p WHERE 1=1";
$params = [];

if ($selected_category !== 'all') {
    $sql .= " AND p.category = ?";
    $params[] = $selected_category;
}

if ($search_query !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

$sql .= " ORDER BY (p.stock <= 0) ASC, RAND()";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$product_images_map = get_product_images_for_ids($pdo, array_column($products, 'product_id'));

$categories = [
    'all'          => 'All Products',
    'computers'    => 'PCs & Workstations',
    'printers'     => 'Printers & Supplies',
    'networking'   => 'Networking Equipment',
    'accessories'  => 'Peripherals & Parts'
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
  <link rel="stylesheet" href="../assets/css/master.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/cart-modal.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
  <link rel="stylesheet" href="../assets/css/shop-cards.css">
  <link rel="stylesheet" href="../assets/css/shop.css">
  <link rel="stylesheet" href="../assets/css/product-modal.css">
  <link rel="stylesheet" href="../assets/css/page-veil.css">
</head>
<body>

  <!-- NAVBAR -->
  <?php 
    require_once __DIR__ . '/../components/header.php'; 
  ?>

  <main class="container shop-main">
  <?php if ($page_hidden): ?>
    <h1 class="hidden"> HIDDEN </h1>
  <?php else: ?>
    <div class="shop-header">
      <div>
        <span class="section-tag">HARDWARE & SUPPLIES</span>
        <h1>TimosaTech Store</h1>
      </div>
      <form method="get" class="shop-search-form">
        <?php if ($selected_category !== 'all'): ?>
          <input type="hidden" name="category" value="<?= htmlspecialchars($selected_category) ?>">
        <?php endif; ?>
        <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search_query) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
      </form>
    </div>

    <div class="shop-layout">
      <!-- Sidebar Categories -->
      <aside class="shop-sidebar">
        <h3>Categories</h3>
        <ul class="category-list">
          <?php foreach ($categories as $key => $label): ?>
            <li>
              <a href="?category=<?= $key ?><?= $search_query !== '' ? '&search=' . urlencode($search_query) : '' ?>" 
                 class="<?= $selected_category === $key ? 'active' : '' ?>">
                <?= htmlspecialchars($label) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </aside>

      <!-- Products Grid -->
      <section style="padding: 0;">
        <?php if (empty($products)): ?>
          <p class="no-products">No products found matching your criteria.</p>
        <?php else: ?>
          <div class="products-grid">
            <?php foreach ($products as $product): 
              $product_images = $product_images_map[$product['product_id']] ?? [];
              $image_urls = get_product_image_urls($product_images);
              $img_src = $image_urls[0];
            ?>
              <div class="product-card">
                <div class="shop-product-thumb">
                  <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                </div>
                <div class="product-body">
                  <h3><?= htmlspecialchars($product['name']) ?></h3>
                  <div class="product-footer">
                    <div class="product-info-row">
                      <span class="product-price">₱<?= number_format($product['price'], 2) ?></span>
                      <span class="product-stock<?= intval($product['stock']) <= 0 ? ' out-of-stock' : '' ?>">
                        <?= intval($product['stock']) > 0 ? intval($product['stock']) . ' in stock' : 'Out of stock' ?>
                      </span>
                    </div>
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
        <?php endif; ?>
      </section>
    </div>
  <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../includes/modals/product-modal.php'; ?>

  <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
  <script src="../assets/js/product-modal.js"></script>

  <!-- INFO: FOOTER SECTION -->
  <?php 
    require_once __DIR__ . '/../components/footer.php'; 
  ?>

  <?php include __DIR__ . '/../includes/modals/login-signup-modal.php'; ?>
  <?php include __DIR__ . '/../includes/modals/cart-modal.php'; ?>
  <script src="../assets/js/auth.js"></script>
  <script src="../assets/js/cart.js"></script>
</body>
</html>