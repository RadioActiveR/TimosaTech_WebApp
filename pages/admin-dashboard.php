<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/product-functions.php';
require_once __DIR__ . '/../includes/order-admin-functions.php';
require_once __DIR__ . '/../includes/activity-log-functions.php';

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

$order_statuses = [
    'pending'    => 'Pending',
    'processing' => 'Processing',
    'shipped'    => 'Shipped',
    'completed'  => 'Completed',
    'cancelled'  => 'Cancelled',
];

// INFO (CRUD log): the fixed set of action/entity values activity_logs
// rows can have — keeps the log filterable instead of relying on free text.
$log_actions = [
    'create'        => 'Create',
    'update'        => 'Update',
    'delete'        => 'Delete',
    'status_change' => 'Status Change',
];
$log_entities = [
    'product' => 'Product',
    'order'   => 'Order',
    'user'    => 'User',
];

$message = '';
$error = '';

// Handle Adding/Editing/Deleting Products and Updating/Deleting Orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if (in_array($_POST['action'], ['add_product', 'edit_product'], true)) {
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
                $new_product_id = add_product_with_image($pdo, $name, $category, $desc, $price, $stock, $base64_data, $mime_type);
                log_activity(
                    $pdo, $_SESSION['u_id'], 'create', 'product', (string) $new_product_id,
                    "Added product \"$name\" (category: $category, price: $" . number_format($price, 2) . ", stock: $stock)"
                );
                $message = "Product added successfully!";
            } elseif ($_POST['action'] === 'edit_product' && $product_id > 0) {
                update_product_with_image($pdo, $product_id, $name, $category, $desc, $price, $stock, $base64_data, $mime_type);
                log_activity(
                    $pdo, $_SESSION['u_id'], 'update', 'product', (string) $product_id,
                    "Updated product \"$name\" (category: $category, price: $" . number_format($price, 2) . ", stock: $stock)"
                );
                $message = "Product updated successfully!";
            }
        } else {
            $error = "Please fill in all required fields.";
        }

    } elseif ($_POST['action'] === 'delete_product') {
        $product_id_post  = intval($_POST['product_id'] ?? 0);
        $existing_product = null;

        if ($product_id_post > 0) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->execute([$product_id_post]);
            $existing_product = $stmt->fetch();
        }

        if ($existing_product) {
            $result = delete_product($pdo, $product_id_post);
            if ($result['success']) {
                log_activity(
                    $pdo, $_SESSION['u_id'], 'delete', 'product', (string) $product_id_post,
                    "Deleted product \"{$existing_product['name']}\""
                );
                $message = "Product \"" . $existing_product['name'] . "\" was deleted.";
            } else {
                $error = $result['error'] ?? "Could not delete that product.";
            }
        } else {
            $error = "Product not found.";
        }

    } elseif ($_POST['action'] === 'update_order_status') {
        $order_id_post = trim($_POST['order_id'] ?? '');
        $new_status    = $_POST['status'] ?? '';

        if ($order_id_post !== '' && array_key_exists($new_status, $order_statuses)) {
            // Fetched before the update so we can log the from -> to transition;
            // also doubles as the "does this order still exist" check.
            $existing_order = get_order_admin($pdo, $order_id_post);

            if ($existing_order && update_order_status($pdo, $order_id_post, $new_status, $_SESSION['u_id'])) {
                if ($existing_order['status'] !== $new_status) {
                    log_activity(
                        $pdo, $_SESSION['u_id'], 'status_change', 'order', $order_id_post,
                        "Changed status from \"{$existing_order['status']}\" to \"$new_status\""
                    );
                }
                $message = "Order #" . $order_id_post . " status updated to " . $order_statuses[$new_status] . ".";
            } else {
                $error = "Could not update that order's status. It may no longer exist.";
            }
        } else {
            $error = "Invalid order status update request.";
        }

    } elseif ($_POST['action'] === 'delete_order') {
        $order_id_post  = trim($_POST['order_id'] ?? '');
        // Fetched before the delete since there's nothing left to describe afterward.
        $existing_order = $order_id_post !== '' ? get_order_admin($pdo, $order_id_post) : null;

        if ($existing_order && delete_order_admin($pdo, $order_id_post)) {
            log_activity(
                $pdo, $_SESSION['u_id'], 'delete', 'order', $order_id_post,
                "Deleted order for {$existing_order['recipient_name']} totaling $" . number_format($existing_order['total_amount'], 2)
            );
            $message = "Order #" . $order_id_post . " was deleted.";
        } else {
            $error = "Could not delete that order.";
        }
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

// Fetch data for the Orders tab (list + optional single-order detail view)
$all_orders           = [];
$order_status_filter  = 'all';
$order_search         = '';
$view_order           = null;
$view_order_items     = [];
$view_order_log       = [];

if ($active_tab === 'orders') {
    $order_status_filter = $_GET['status'] ?? 'all';
    if ($order_status_filter !== 'all' && !array_key_exists($order_status_filter, $order_statuses)) {
        $order_status_filter = 'all';
    }
    $order_search = trim($_GET['search'] ?? '');
    $all_orders   = get_orders_admin($pdo, $order_status_filter, $order_search);

    if (isset($_GET['view_id']) && $_GET['view_id'] !== '') {
        $view_order = get_order_admin($pdo, $_GET['view_id']);
        if ($view_order) {
            $view_order_items = get_order_items_admin($pdo, $view_order['order_id']);
            $view_order_log   = get_order_status_log($pdo, $view_order['order_id']);
        }
    }
}

// Fetch data for the CRUD Log tab
$activity_logs     = [];
$log_action_filter = 'all';
$log_entity_filter = 'all';
$log_search        = '';

if ($active_tab === 'crud_log') {
    $log_action_filter = $_GET['log_action'] ?? 'all';
    if ($log_action_filter !== 'all' && !array_key_exists($log_action_filter, $log_actions)) {
        $log_action_filter = 'all';
    }
    $log_entity_filter = $_GET['log_entity'] ?? 'all';
    if ($log_entity_filter !== 'all' && !array_key_exists($log_entity_filter, $log_entities)) {
        $log_entity_filter = 'all';
    }
    $log_search    = trim($_GET['log_search'] ?? '');
    $activity_logs = get_activity_logs($pdo, $log_action_filter, $log_entity_filter, $log_search);
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

    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      text-transform: capitalize;
      white-space: nowrap;
    }
    .status-pending    { background: rgba(250, 204, 21, 0.12); color: #facc15; }
    .status-processing { background: rgba(79, 143, 247, 0.12); color: #4f8ff7; }
    .status-shipped     { background: rgba(10, 221, 238, 0.12); color: #0adde0; }
    .status-completed   { background: rgba(74, 222, 128, 0.12); color: #4ade80; }
    .status-cancelled   { background: rgba(248, 113, 113, 0.12); color: #f87171; }

    .log-action-create        { background: rgba(74, 222, 128, 0.12); color: #4ade80; }
    .log-action-update        { background: rgba(79, 143, 247, 0.12); color: #4f8ff7; }
    .log-action-delete        { background: rgba(248, 113, 113, 0.12); color: #f87171; }
    .log-action-status_change { background: rgba(10, 221, 238, 0.12); color: #0adde0; }

    .admin-filter-bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 10px; }
    .admin-filter-bar select,
    .admin-filter-bar input[type="text"] {
      background: var(--bg1);
      border: 1px solid var(--bg-card-border);
      color: var(--text-primary);
      padding: 9px 12px;
      border-radius: 8px;
      font-size: 0.85rem;
      font-family: var(--font-body);
    }

    .admin-row-actions { display: flex; gap: 8px; align-items: center; }
    .admin-row-actions form { display: inline; margin: 0; }

    .order-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
    .order-detail-panel {
      background: #0d192b;
      border: 1px solid #1a2f4c;
      border-radius: 8px;
      padding: 20px;
    }
    .order-detail-panel h3 { margin-top: 0; color: #0adde0; font-size: 1rem; }
    .order-detail-panel p { color: #cbd5e1; font-size: 0.9rem; margin: 4px 0; }

    .order-status-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; padding-top: 20px; }
    .order-status-log { margin-top: 10px; font-size: 0.8rem; color: var(--text-muted); max-height: 160px; overflow-y: auto; padding-left: 4px; }
    .order-status-log li { list-style: none; padding: 6px 0; border-bottom: 1px solid #1a2f4c; }
    .order-status-log li:last-child { border-bottom: none; }

    @media (max-width: 860px) {
      .order-detail-grid { grid-template-columns: 1fr; }
    }
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
                    <div class="admin-row-actions">
                      <a href="?tab=products&edit_id=<?= $p['product_id'] ?>" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem;">Edit</a>
                      <form method="post" onsubmit="return confirm('Delete \'<?= htmlspecialchars(addslashes($p['name'])) ?>\'? This cannot be undone.');">
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                        <button type="submit" class="btn btn-outline" style="border-color: #f87171; color: #f87171; padding: 4px 10px; font-size: 0.8rem;">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>

      <?php elseif ($active_tab === 'orders'): ?>
        <section class="admin-panel">

          <?php if ($view_order): ?>
            <!-- ============ ORDER DETAIL VIEW ============ -->
            <a href="?tab=orders" class="btn btn-outline" style="margin-bottom: 20px; padding: 6px 14px; font-size: 0.8rem;">&larr; Back to Orders</a>

            <?php if ($message): ?><p style="color: #0ADDEE; margin-bottom: 15px;"><?= htmlspecialchars($message) ?></p><?php endif; ?>
            <?php if ($error): ?><p style="color: #f87171; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

            <h2 style="display:flex; align-items:center; gap: 14px; flex-wrap: wrap;">
              Order #<?= htmlspecialchars($view_order['order_id']) ?>
              <span class="status-badge status-<?= htmlspecialchars($view_order['status']) ?>">
                <?= htmlspecialchars($order_statuses[$view_order['status']] ?? $view_order['status']) ?>
              </span>
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
              Placed <?= date('M j, Y \a\t g:i A', strtotime($view_order['created_at'])) ?>
            </p>

            <div class="order-detail-grid">
              <div class="order-detail-panel">
                <h3>Customer</h3>
                <p><strong><?= htmlspecialchars($view_order['username']) ?></strong></p>
                <p><?= htmlspecialchars($view_order['email']) ?></p>
              </div>

              <div class="order-detail-panel">
                <h3>Shipping Details</h3>
                <p><?= htmlspecialchars($view_order['recipient_name']) ?></p>
                <p><?= htmlspecialchars($view_order['phone_number']) ?></p>
                <p>
                  <?= htmlspecialchars($view_order['address_line1']) ?><?= $view_order['address_line2'] ? ', ' . htmlspecialchars($view_order['address_line2']) : '' ?><br>
                  <?= htmlspecialchars($view_order['city']) ?>, <?= htmlspecialchars($view_order['province']) ?> <?= htmlspecialchars($view_order['postal_code']) ?>
                </p>
              </div>

              <div class="order-detail-panel">
                <h3>Payment</h3>
                <p><?= htmlspecialchars(ucwords(str_replace('_', ' ', $view_order['payment_method']))) ?></p>
                <p style="margin-top: 10px;">Subtotal: $<?= number_format($view_order['subtotal'], 2) ?></p>
                <p>Shipping: <?= $view_order['shipping_fee'] > 0 ? '$' . number_format($view_order['shipping_fee'], 2) : 'Free' ?></p>
                <p style="color: #0adde0; font-weight: 700;">Total: $<?= number_format($view_order['total_amount'], 2) ?></p>
              </div>

              <div class="order-detail-panel">
                <h3>Update Status</h3>
                <form method="post" class="order-status-form">
                  <input type="hidden" name="action" value="update_order_status">
                  <input type="hidden" name="order_id" value="<?= htmlspecialchars($view_order['order_id']) ?>">
                  <select name="status" style="background: var(--bg1); border: 1px solid var(--bg-card-border); color: var(--text-primary); padding: 9px 12px; border-radius: 8px;">
                    <?php foreach ($order_statuses as $val => $lbl): ?>
                      <option value="<?= $val ?>" <?= $view_order['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-primary" style="padding: 9px 18px; font-size: 0.85rem;">Save Status</button>
                </form>

                <form method="post" onsubmit="return confirm('Delete order #<?= htmlspecialchars($view_order['order_id']) ?>? This cannot be undone.');" style="margin-top: 14px;">
                  <input type="hidden" name="action" value="delete_order">
                  <input type="hidden" name="order_id" value="<?= htmlspecialchars($view_order['order_id']) ?>">
                  <button type="submit" class="btn btn-outline" style="border-color: #f87171; color: #f87171; padding: 8px 16px; font-size: 0.8rem;">Delete Order</button>
                </form>

                <?php if (!empty($view_order_log)): ?>
                  <h3 style="margin-top: 18px;">Status History</h3>
                  <ul class="order-status-log">
                    <?php foreach ($view_order_log as $log): ?>
                      <li>
                        <?= htmlspecialchars($log['old_status'] ?? 'created') ?> &rarr; <strong><?= htmlspecialchars($log['new_status']) ?></strong>
                        by <?= htmlspecialchars($log['changed_by_username'] ?? 'system') ?>
                        on <?= date('M j, g:i A', strtotime($log['changed_at'])) ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </div>

            <h3 style="margin-top: 30px;">Items Ordered</h3>
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Unit Price</th>
                  <th>Qty</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($view_order_items as $item): ?>
                  <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td>$<?= number_format($item['unit_price'], 2) ?></td>
                    <td><?= intval($item['quantity']) ?></td>
                    <td>$<?= number_format($item['subtotal'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

          <?php elseif (isset($_GET['view_id'])): ?>
            <!-- Someone followed a link to an order that no longer exists -->
            <p style="color: #f87171;">Order not found.</p>
            <a href="?tab=orders" class="btn btn-outline" style="margin-top: 15px;">&larr; Back to Orders</a>

          <?php else: ?>
            <!-- ============ ORDERS LIST VIEW ============ -->
            <h2>Orders Management</h2>

            <?php if ($message): ?><p style="color: #0ADDEE; margin-bottom: 15px;"><?= htmlspecialchars($message) ?></p><?php endif; ?>
            <?php if ($error): ?><p style="color: #f87171; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

            <form method="get" class="admin-filter-bar">
              <input type="hidden" name="tab" value="orders">
              <select name="status" onchange="this.form.submit()">
                <option value="all" <?= $order_status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <?php foreach ($order_statuses as $val => $lbl): ?>
                  <option value="<?= $val ?>" <?= $order_status_filter === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
              </select>
              <input type="text" name="search" placeholder="Search order ID, customer, or email..." value="<?= htmlspecialchars($order_search) ?>">
              <button type="submit" class="btn btn-primary" style="padding: 9px 18px; font-size: 0.85rem;">Filter</button>
            </form>

            <?php if (empty($all_orders)): ?>
              <p class="admin-panel-placeholder">No orders found.</p>
            <?php else: ?>
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($all_orders as $o): ?>
                    <tr>
                      <td>#<?= htmlspecialchars($o['order_id']) ?></td>
                      <td>
                        <?= htmlspecialchars($o['username']) ?><br>
                        <span style="color: var(--text-faint); font-size: 0.78rem;"><?= htmlspecialchars($o['email']) ?></span>
                      </td>
                      <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                      <td>$<?= number_format($o['total_amount'], 2) ?></td>
                      <td>
                        <span class="status-badge status-<?= htmlspecialchars($o['status']) ?>">
                          <?= htmlspecialchars($order_statuses[$o['status']] ?? $o['status']) ?>
                        </span>
                      </td>
                      <td>
                        <a href="?tab=orders&view_id=<?= urlencode($o['order_id']) ?>" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem;">View</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          <?php endif; ?>
        </section>

      <?php elseif ($active_tab === 'crud_log'): ?>
        <section class="admin-panel">
          <h2>CRUD Log</h2>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 15px;">
            A record of every create, update, delete, and status-change action taken across the admin dashboard.
          </p>

          <form method="get" class="admin-filter-bar">
            <input type="hidden" name="tab" value="crud_log">
            <select name="log_action" onchange="this.form.submit()">
              <option value="all" <?= $log_action_filter === 'all' ? 'selected' : '' ?>>All Actions</option>
              <?php foreach ($log_actions as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $log_action_filter === $val ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
            <select name="log_entity" onchange="this.form.submit()">
              <option value="all" <?= $log_entity_filter === 'all' ? 'selected' : '' ?>>All Entities</option>
              <?php foreach ($log_entities as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $log_entity_filter === $val ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" name="log_search" placeholder="Search detail, entity ID, or admin..." value="<?= htmlspecialchars($log_search) ?>">
            <button type="submit" class="btn btn-primary" style="padding: 9px 18px; font-size: 0.85rem;">Filter</button>
          </form>

          <?php if (empty($activity_logs)): ?>
            <p class="admin-panel-placeholder">No activity logged yet.</p>
          <?php else: ?>
            <table class="admin-table">
              <thead>
                <tr>
                  <th>When</th>
                  <th>Admin</th>
                  <th>Action</th>
                  <th>Entity</th>
                  <th>Detail</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($activity_logs as $log): ?>
                  <tr>
                    <td><?= date('M j, Y g:i A', strtotime($log['created_at'])) ?></td>
                    <td><?= htmlspecialchars($log['username'] ?? 'system') ?></td>
                    <td>
                      <span class="status-badge log-action-<?= htmlspecialchars($log['action']) ?>">
                        <?= htmlspecialchars($log_actions[$log['action']] ?? $log['action']) ?>
                      </span>
                    </td>
                    <td>
                      <?= htmlspecialchars($log_entities[$log['entity_type']] ?? $log['entity_type']) ?>
                      <?php if ($log['entity_id']): ?>
                        <span style="color: var(--text-faint); font-size: 0.78rem;">#<?= htmlspecialchars($log['entity_id']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['detail'] ?? '') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <p style="color: var(--text-faint); font-size: 0.78rem; margin-top: 10px;">Showing the most recent 200 entries.</p>
          <?php endif; ?>
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