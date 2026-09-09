<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/product-functions.php';

// Access Control Protection
if (!isset($_SESSION['u_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../pages/homepage.php");
    exit();
}

$page_title = "Timosa Tech - Admin Dashboard";
$user_name = $_SESSION['username'] ?? 'Admin';

$admin_tabs = [
    'tickets'  => 'Tickets',
    'orders'   => 'Orders',
    'products' => 'Products',
    'support'  => 'Customer Support Chat',
    'metrics'  => 'Metrics',
    'crud_log' => 'CRUD Log'
];

$active_tab = $_GET['tab'] ?? 'tickets';
if (!array_key_exists($active_tab, $admin_tabs)) {
    $active_tab = 'tickets';
}

$message = '';
$error = '';

// Handle Adding or Editing Products
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $name       = trim($_POST['name'] ?? '');
    $category   = trim($_POST['category'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $price      = floatval($_POST['price'] ?? 0);
    $stock      = intval($_POST['stock'] ?? 0);
    $product_id = intval($_POST['product_id'] ?? 0);

    $base64_data = null;
    $mime_type   = 'image/png';

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['product_image']['tmp_name'];
        $file_type = $_FILES['product_image']['type'];

        $file_contents = file_get_contents($file_tmp);
        $base64_data   = base64_encode($file_contents);
        $mime_type     = $file_type;
    }

    if ($name && $category && $price >= 0) {
        if ($_POST['action'] === 'add_product') {
            add_product_with_image($pdo, $name, $category, $desc, $price, $stock, $base64_data, $mime_type);
            $message = "Product added successfully!";
        } elseif ($_POST['action'] === 'edit_product' && $product_id > 0) {
            update_product_with_image($pdo, $product_id, $name, $category, $desc, $price, $stock, $base64_data, $mime_type);
            $message = "Product updated successfully!";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch all products for the Products management table
$all_products = [];
if ($active_tab === 'products') {
    $stmt = $pdo->query("SELECT p.*, i.image_data, i.mime_type FROM products p LEFT JOIN images i ON p.image_id = i.image_id ORDER BY p.product_id DESC");
    $all_products = $stmt->fetchAll();
}

// Check if editing a specific product
$edit_product = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="../styles/styles.css">
  <style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .admin-table th, .admin-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--bg-card-border, #1a2f4c); color: #cbd5e1; }
    .admin-table th { background: #08121e; color: #0adde0; }
  </style>
</head>
<body class="admin-body">

  <header class="navbar admin-navbar">
    <div class="container admin-nav-container">
      <div class="admin-brand">
        <a href="homepage.php" class="logo">
          <img class="img-logo" src="../images/TimosaTechLogo.png" alt="Logo">
          <span class="logoname1">TIMOSA</span><span class="logoname2">TECH</span>
        </a>
        <span class="admin-badge">ADMINISTRATOR DASHBOARD</span>
      </div>
      <div class="nav-cta">
        <span class="nav-greeting">Hi, <?= htmlspecialchars($user_name) ?></span>
        <a href="homepage.php" class="btn btn-outline admin-nav-btn">View Main Site</a>
        <a href="../includes/logout.php" class="btn btn-outline">Log Out</a>
      </div>
    </div>
  </header>

  <div class="admin-layout container">
    <nav class="admin-tabs-nav">
      <?php foreach ($admin_tabs as $key => $label): ?>
        <a href="?tab=<?= $key ?>" class="admin-tab-link <?= $active_tab === $key ? 'active' : '' ?>">
          <?= htmlspecialchars($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <main class="admin-content-area">
      <?php if ($active_tab === 'products'): ?>
        <section class="admin-panel">
          <h2>Products Management</h2>

          <?php if ($message): ?><p style="color: #0ADDEE; margin-bottom: 15px;"><?= htmlspecialchars($message) ?></p><?php endif; ?>
          <?php if ($error): ?><p style="color: #f87171; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

          <!-- ADD/EDIT FORM -->
          <div style="background: #0d192b; border: 1px solid #1a2f4c; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h3><?= $edit_product ? 'Edit Product #' . $edit_product['product_id'] : 'Add New Product' ?></h3>
            <form method="post" enctype="multipart/form-data" class="auth-form active" style="max-width: 600px;">
              <input type="hidden" name="action" value="<?= $edit_product ? 'edit_product' : 'add_product' ?>">
              <?php if ($edit_product): ?>
                <input type="hidden" name="product_id" value="<?= $edit_product['product_id'] ?>">
              <?php endif; ?>
              
              <div class="auth-field">
                <label>Product Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($edit_product['name'] ?? '') ?>" required>
              </div>

              <div class="auth-field">
                <label>Category *</label>
                <select name="category" required style="background: var(--bg1); border: 1px solid var(--bg-card-border); padding: 11px; color: var(--text-primary); border-radius: 8px;">
                  <?php 
                  $cats = ['computers' => 'PCs & Workstations', 'printers' => 'Printers & Supplies', 'networking' => 'Networking Equipment', 'accessories' => 'Peripherals & Parts'];
                  foreach ($cats as $val => $lbl): 
                    $selected = ($edit_product['category'] ?? '') === $val ? 'selected' : '';
                  ?>
                    <option value="<?= $val ?>" <?= $selected ?>><?= $lbl ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="auth-field">
                <label>Description</label>
                <textarea name="description" rows="3" style="background: var(--bg1); border: 1px solid var(--bg-card-border); padding: 11px; color: var(--text-primary); border-radius: 8px;"><?= htmlspecialchars($edit_product['description'] ?? '') ?></textarea>
              </div>

              <div style="display: flex; gap: 15px;">
                <div class="auth-field" style="flex: 1;">
                  <label>Price ($) *</label>
                  <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($edit_product['price'] ?? '') ?>" required>
                </div>
                <div class="auth-field" style="flex: 1;">
                  <label>Stock Quantity *</label>
                  <input type="number" name="stock" value="<?= htmlspecialchars($edit_product['stock'] ?? '10') ?>" required>
                </div>
              </div>

              <div class="auth-field">
                <label>Product Image <?= $edit_product ? '(Leave blank to keep current image)' : '' ?></label>
                <input type="file" name="product_image" accept="image/*">
              </div>

              <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn btn-primary"><?= $edit_product ? 'Update Product' : 'Add Product' ?></button>
                <?php if ($edit_product): ?>
                  <a href="?tab=products" class="btn btn-outline">Cancel Edit</a>
                <?php endif; ?>
              </div>
            </form>
          </div>

          <!-- INVENTORY TABLE -->
          <h3>Current Inventory</h3>
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($all_products as $p): ?>
                <tr>
                  <td>#<?= $p['product_id'] ?></td>
                  <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                  <td><?= htmlspecialchars($p['category']) ?></td>
                  <td>$<?= number_format($p['price'], 2) ?></td>
                  <td><?= $p['stock'] ?></td>
                  <td>
                    <a href="?tab=products&edit_id=<?= $p['product_id'] ?>" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem;">Edit</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>
      <?php else: ?>
        <section class="admin-panel">
          <h2><?= htmlspecialchars($admin_tabs[$active_tab]) ?></h2>
          <p class="admin-panel-placeholder"><?= htmlspecialchars($admin_tabs[$active_tab]) ?> panel layout will go here.</p>
        </section>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>