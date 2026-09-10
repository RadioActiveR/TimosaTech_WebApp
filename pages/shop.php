<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cart-functions.php';

$is_logged_in = isset($_SESSION['u_id']);
$user_name    = $_SESSION['username'] ?? '';
$is_admin     = ($_SESSION['user_role'] ?? '') === 'admin';
$cart_count   = $is_logged_in ? get_cart_count($pdo, $_SESSION['u_id']) : 0;

$page_title = "Timosa Tech - Store";

// Category Filter Logic
$selected_category = $_GET['category'] ?? 'all';
$search_query      = trim($_GET['search'] ?? '');

$sql = "SELECT p.*, i.image_data, i.mime_type 
        FROM products p 
        LEFT JOIN images i ON p.image_id = i.image_id WHERE 1=1";
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

$sql .= " ORDER BY p.product_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

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
  <link rel="stylesheet" href="../styles/styles.css">
  <style>
    .profile-circle-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }
    .profile-circle-btn svg {
      width: 20px;
      height: 20px;
    }
    .profile-circle-btn:hover {
      background-color: rgba(255, 255, 255, 0.1);
      transform: scale(1.05);
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <header class="navbar">
    <div class="container">
      <div class="logo">
        <img class="img-logo" src="../images/TimosaTechLogo.png" alt="Timosa Tech Logo">
        <a class="logoname1" href="../index.php">TIMOSA</a><a class="logoname2" href="../index.php">TECH</a>
      </div>
      <nav class="nav-links">
        <a href="../index.php">Home</a>
        <a href="shop.php" class="active">Shop</a>
        <a href="#">Services</a>
        <a href="#">About</a>
        <a href="#">Contact</a>
      </nav>
      <div class="nav-cta">
        <?php if ($is_logged_in): ?>
          
          <span class="nav-greeting">Hi, <?= htmlspecialchars(
              mb_strlen($user_name) > 13 
                  ? explode(' ', trim($user_name))[0] 
                  : $user_name
          ) ?></span>

          <?php if ($is_admin): ?>
            <a href="admin-dashboard.php" class="btn btn-outline admin-nav-btn">Admin Dash</a>
          <?php endif; ?>

          <button type="button" class="btn btn-outline cart-nav-btn" data-open-cart>
            Cart <span class="cart-count-badge" style="<?= $cart_count === 0 ? 'display:none;' : '' ?>"><?= $cart_count ?></span>
          </button>

          <!-- Circular Profile Button -->
          <a href="profile.php" class="btn btn-outline profile-circle-btn" title="My Profile" aria-label="My Profile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </a>

        <?php else: ?>
          <a href="#" class="btn btn-outline" data-open-auth="login">Log In</a>
        <?php endif; ?>

          

      </div>
    </div>
  </header>

  <main class="container shop-main">
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
              $img_src = !empty($product['image_data']) 
                ? 'data:' . htmlspecialchars($product['mime_type'] ?? 'image/png') . ';base64,' . $product['image_data'] 
                : '../images/workstation-rig.png';
            ?>
              <div class="product-card">
                <div class="shop-product-thumb">
                  <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                </div>
                <div class="product-body">
                  <h3><?= htmlspecialchars($product['name']) ?></h3>
                  <div class="product-footer">
                    <span class="product-price">$<?= number_format($product['price'], 2) ?></span>
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
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>
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
          <button class="btn btn-primary" id="modalAddToCart" type="button">Add to Cart</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const modal = document.getElementById('productModal');
      const closeModal = document.getElementById('closeModal');
      const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;

      document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', (e) => {
          e.preventDefault();

          if (!isLoggedIn) {
            const authOverlay = document.getElementById('authOverlay');
            if (authOverlay) {
              authOverlay.classList.add('active');
              document.body.style.overflow = 'hidden';
            }
            return;
          }

          modal.dataset.currentProductId = button.dataset.id;
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
  <?php include __DIR__ . '/../includes/cart-modal.php'; ?>
  <script src="../js/auth.js"></script>
  <script src="../js/cart.js"></script>
</body>
</html>