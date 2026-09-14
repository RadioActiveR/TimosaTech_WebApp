<?php

/* INFO: Linked Files:

    config/db.php
    includes/product-functions.php
    includes/order-admin-functions.php
    includes/activity-log-functions.php

*/

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../admin/includes/functions/product-admin-functions.php';
require_once __DIR__ . '/../admin/includes/functions/order-admin-functions.php';
require_once __DIR__ . '/../admin/includes/functions/activity-log-admin-functions.php';
require_once __DIR__ . '/../includes/functions/site-control-functions.php';
require_once __DIR__ . '/../includes/functions/chat-functions.php';
require_once __DIR__ . '/../helpers/icons.php';

// Access Control Protection
if (!isset($_SESSION['u_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../pages/homepage.php");
    exit();
}

$page_title = "Timosa Tech - Admin Dashboard";
$current_page = 'admin';
$user_name = $_SESSION['username'] ?? 'Admin';

$admin_tabs = [
    'overview'       => 'Overview',
    'site_controls' => 'Site Controls',
    'tickets'       => 'Tickets',
    'orders'        => 'Orders',
    'products'      => 'Products',
    'support'       => 'Customer Support Chat',
    'crud_log'      => 'CRUD Log'
];

$controllable_pages = get_controllable_pages();
$controllable_modals = get_controllable_modals();

$active_tab = $_GET['tab'] ?? 'overview';
if (!array_key_exists($active_tab, $admin_tabs)) {
    $active_tab = 'overview';
}

$order_statuses = [
    'pending'    => 'Pending',
    'processing' => 'Processing',
    'shipped'    => 'Shipped',
    'completed'  => 'Completed',
    'cancelled'  => 'Cancelled',
];

$log_actions = [
    'create'        => 'Create',
    'update'        => 'Update',
    'delete'        => 'Delete',
    'status_change' => 'Status Change',
];
$log_entities = [
    'product'         => 'Product',
    'order'           => 'Order',
    'user'            => 'User',
    'page_visibility' => 'Page Visibility',
    'modal_visibility'=> 'Modal Visibility',
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

        $uploaded_images = $_FILES['images'] ?? [];
        $remove_image_ids = array_map('intval', $_POST['remove_images'] ?? []);

        if (!$name || !$category) {
            $error = "Please fill in all required fields.";
        } elseif ($price < 0 || $stock < 0) {
            $error = "Price and stock quantity cannot be negative.";
        } else {
            if ($_POST['action'] === 'add_product') {
                $new_product_id = add_product_with_images($pdo, $name, $category, $desc, $price, $stock, $uploaded_images);
                log_activity(
                    $pdo, $_SESSION['u_id'], 'create', 'product', (string) $new_product_id,
                    "Added product \"$name\" (category: $category, price: $" . number_format($price, 2) . ", stock: $stock)"
                );
                $message = "Product added successfully!";
            } elseif ($_POST['action'] === 'edit_product' && $product_id > 0) {
                update_product_with_images($pdo, $product_id, $name, $category, $desc, $price, $stock, $uploaded_images, $remove_image_ids);
                log_activity(
                    $pdo, $_SESSION['u_id'], 'update', 'product', (string) $product_id,
                    "Updated product \"$name\" (category: $category, price: $" . number_format($price, 2) . ", stock: $stock)"
                );
                $message = "Product updated successfully!";
            }
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

    } elseif ($_POST['action'] === 'toggle_page_visibility') {
        $page_key = trim($_POST['page_key'] ?? '');
        $hidden   = ($_POST['hidden'] ?? '0') === '1';

        if (array_key_exists($page_key, $controllable_pages)) {
            set_page_hidden($pdo, $page_key, $hidden);
            log_activity(
                $pdo, $_SESSION['u_id'], 'update', 'page_visibility', $page_key,
                ($hidden ? 'Hid' : 'Unhid') . " the \"{$controllable_pages[$page_key]}\" page"
            );
            $message = $controllable_pages[$page_key] . ($hidden ? ' is now hidden.' : ' is now visible.');
        } else {
            $error = "Unknown page.";
        }

    } elseif ($_POST['action'] === 'toggle_all_pages') {
        $hidden = ($_POST['hidden'] ?? '0') === '1';

        set_all_pages_hidden($pdo, $hidden);
        log_activity(
            $pdo, $_SESSION['u_id'], 'update', 'page_visibility', 'all',
            $hidden ? 'Hid every site page' : 'Made every site page visible'
        );
        $message = $hidden ? 'All pages are now hidden.' : 'All pages are now visible.';

    } elseif ($_POST['action'] === 'toggle_modal_visibility') {
        $modal_key = trim($_POST['modal_key'] ?? '');
        $hidden    = ($_POST['hidden'] ?? '0') === '1';

        if (array_key_exists($modal_key, $controllable_modals)) {
            set_modal_hidden($pdo, $modal_key, $hidden);
            log_activity(
                $pdo, $_SESSION['u_id'], 'update', 'modal_visibility', $modal_key,
                ($hidden ? 'Hid' : 'Unhid') . " the \"{$controllable_modals[$modal_key]}\""
            );
            $message = $controllable_modals[$modal_key] . ($hidden ? ' is now hidden.' : ' is now visible.');
        } else {
            $error = "Unknown modal.";
        }

    } elseif ($_POST['action'] === 'toggle_all_modals') {
        $hidden = ($_POST['hidden'] ?? '0') === '1';

        set_all_modals_hidden($pdo, $hidden);
        log_activity(
            $pdo, $_SESSION['u_id'], 'update', 'modal_visibility', 'all',
            $hidden ? 'Hid every modal' : 'Made every modal visible'
        );
        $message = $hidden ? 'All modals are now hidden.' : 'All modals are now visible.';

    } elseif ($_POST['action'] === 'delete_order') {
        $order_id_post  = trim($_POST['order_id'] ?? '');
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

    } elseif ($_POST['action'] === 'send_admin_reply') {
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $reply_message   = trim($_POST['message'] ?? '');

        if ($conversation_id > 0 && $reply_message !== '') {
            assign_conversation($pdo, $conversation_id, $_SESSION['u_id']);
            add_chat_message($pdo, $conversation_id, 'admin', $_SESSION['u_id'], $reply_message);
        } else {
            $error = "Reply message can't be empty.";
        }
    }
}

// Fetch all products for the Products management table
$all_products = [];
$all_products_images = [];
if ($active_tab === 'products') {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY product_id DESC");
    $all_products = $stmt->fetchAll();
    $all_products_images = get_product_images_for_ids($pdo, array_column($all_products, 'product_id'));
}

// Check if editing a specific product
$edit_product = null;
$edit_product_images = [];
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch();
    if ($edit_product) {
        $edit_product_images = get_product_images($pdo, $edit_id);
    }
}

// Fetch data for the Orders tab
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

// Fetch data for the Site Controls tab
$page_visibility  = [];
$modal_visibility = [];
$all_pages_hidden = false;
$all_modals_hidden = false;
if ($active_tab === 'site_controls') {
    $page_visibility   = get_all_page_visibility($pdo);
    $modal_visibility  = get_all_modal_visibility($pdo);
    $all_pages_hidden  = are_all_pages_hidden($pdo);
    $all_modals_hidden = are_all_modals_hidden($pdo);
}

// Fetch data for Metrics tab
$metrics_data = [];
if ($active_tab === 'overview') {
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;
}

// Fetch data for the Customer Support Chat tab
$all_conversations        = [];
$selected_conversation_id = 0;
$selected_conversation    = null;
$selected_messages        = [];
if ($active_tab === 'support') {
    $all_conversations        = get_all_conversations_for_admin($pdo);
    $selected_conversation_id = intval($_GET['conversation_id'] ?? 0);
    if ($selected_conversation_id > 0) {
        $selected_conversation = get_conversation($pdo, $selected_conversation_id);
        if ($selected_conversation) {
            $selected_messages = get_conversation_messages($pdo, $selected_conversation_id);
        }
    }
}
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
  <link rel="stylesheet" href="assets/css/admin-portal.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
</head>
<body class="admin-body">

  <!-- NAVBAR -->
  <?php 
    require_once __DIR__ . '/../components/header.php'; 
  ?>

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
                  <input type="number" step="0.01" min="0" name="price" value="<?= htmlspecialchars($edit_product['price'] ?? '') ?>" required>
                </div>
                <div class="auth-field" style="flex: 1;">
                  <label>Stock Quantity *</label>
                  <input type="number" min="0" name="stock" value="<?= htmlspecialchars($edit_product['stock'] ?? '10') ?>" required>
              </div>
              </div>

              <div class="auth-field">
                <label>Product Images<?= $edit_product ? ' (add more below, or remove existing ones)' : '' ?></label>
                <input type="file" name="images[]" id="productImagesInput" class="admin-file-input" accept="image/*" multiple>
                <div class="admin-image-preview-row" id="newImagePreviewRow"></div>
              </div>

              <?php if ($edit_product && !empty($edit_product_images)): ?>
                <div class="auth-field">
                  <label>Current Images <span style="font-weight: 400; color: var(--text-muted);">(check to remove on save)</span></label>
                  <div class="admin-image-preview-row">
                    <?php foreach ($edit_product_images as $img): ?>
                      <label class="admin-image-thumb-wrap">
                        <img src="../<?= htmlspecialchars($img['image_path']) ?>" alt="">
                        <?php if ($img['is_primary']): ?><span class="admin-image-primary-badge">Primary</span><?php endif; ?>
                        <span class="admin-image-remove-overlay">
                          <input type="checkbox" name="remove_images[]" value="<?= $img['product_image_id'] ?>">
                          Remove
                        </span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn btn-primary"><?= $edit_product ? 'Update Product' : 'Add Product' ?></button>
                <?php if ($edit_product): ?>
                  <a href="?tab=products" class="btn btn-outline">Cancel Edit</a>
                <?php endif; ?>
              </div>
            </form>
          </div>

          <script>
            (function () {
              const input = document.getElementById('productImagesInput');
              const row = document.getElementById('newImagePreviewRow');
              if (!input || !row) return;

              // The native file input replaces its whole selection every
              // time you open the picker, so we track the "real" list of
              // chosen files ourselves and rewrite input.files from it —
              // that's what lets picks from separate picker sessions add
              // up instead of overwriting each other.
              let selectedFiles = [];

              function syncInputFiles() {
                const dt = new DataTransfer();
                selectedFiles.forEach((file) => dt.items.add(file));
                input.files = dt.files;
              }

              function renderPreviews() {
                row.innerHTML = '';

                selectedFiles.forEach((file, index) => {
                  const reader = new FileReader();
                  reader.onload = (ev) => {
                    const wrap = document.createElement('div');
                    wrap.className = 'admin-image-thumb-wrap';
                    wrap.innerHTML = `
                      <img src="${ev.target.result}" alt="">
                      <button type="button" class="admin-image-remove-btn" aria-label="Remove image">&times;</button>
                    `;
                    wrap.querySelector('.admin-image-remove-btn').addEventListener('click', () => {
                      selectedFiles.splice(index, 1);
                      syncInputFiles();
                      renderPreviews();
                    });
                    row.appendChild(wrap);
                  };
                  reader.readAsDataURL(file);
                });
              }

              input.addEventListener('change', (e) => {
                selectedFiles = selectedFiles.concat(Array.from(e.target.files));
                syncInputFiles();
                renderPreviews();
              });
            })();
          </script>

          <!-- INVENTORY TABLE -->
          <h3>Current Inventory</h3>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Image</th>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($all_products as $p):
                $p_images = $all_products_images[$p['product_id']] ?? [];
                $p_primary = get_primary_image($p_images);

                $p_stock = (int) $p['stock'];
                if ($p_stock <= 10) {
                    $stock_level = 'critical';
                } elseif ($p_stock < 50) {
                    $stock_level = 'low';
                } else {
                    $stock_level = 'good';
                }
              ?>
                <tr class="stock-row-<?= $stock_level ?>">
                  <td>
                    <?php if ($p_primary): ?>
                      <img class="admin-table-thumb" src="../<?= htmlspecialchars($p_primary['image_path']) ?>" alt="">
                    <?php else: ?>
                      <img class="admin-table-thumb" src="../assets/images/workstation-rig.png" alt="">
                    <?php endif; ?>
                  </td>
                  <td>#<?= $p['product_id'] ?></td>
                  <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                  <td><?= htmlspecialchars($p['category']) ?></td>
                  <td>₱<?= number_format($p['price'], 2) ?></td>
                  <td><span class="stock-badge stock-badge-<?= $stock_level ?>"><?= $p_stock ?></span></td>
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
            <!-- ORDER DETAIL VIEW -->
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
                <p>Shipping: <?= $view_order['shipping_fee'] > 0 ? '₱' . number_format($view_order['shipping_fee'], 2) : 'Free' ?></p>
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
                        <?= htmlspecialchars($log['old_status'] ?? 'created') ?> -> <strong><?= htmlspecialchars($log['new_status']) ?></strong>
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
                    <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                    <td><?= intval($item['quantity']) ?></td>
                    <td>₱<?= number_format($item['subtotal'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

          <?php elseif (isset($_GET['view_id'])): ?>
            <p style="color: #f87171;">Order not found.</p>
            <a href="?tab=orders" class="btn btn-outline" style="margin-top: 15px;">&larr; Back to Orders</a>

          <?php else: ?>
            <!-- ORDERS LIST VIEW -->
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
                      <td>₱<?= number_format($o['total_amount'], 2) ?></td>
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

      <?php elseif ($active_tab === 'overview'): ?>
        <section class="admin-panel">
          <h2>Metrics Dashboard</h2>
          <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">
            Overview of key system metrics and statistics.
          </p>

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="order-detail-panel">
              <h3>Total Users</h3>
              <p style="font-size: 1.8rem; font-weight: bold; color: #0adde0; margin-top: 10px;"><?= number_format($total_users) ?></p>
            </div>
            <div class="order-detail-panel">
              <h3>Total Products</h3>
              <p style="font-size: 1.8rem; font-weight: bold; color: #4ade80; margin-top: 10px;"><?= number_format($total_products) ?></p>
            </div>
            <div class="order-detail-panel">
              <h3>Total Orders</h3>
              <p style="font-size: 1.8rem; font-weight: bold; color: #4f8ff7; margin-top: 10px;"><?= number_format($total_orders) ?></p>
            </div>
            <div class="order-detail-panel">
              <h3>Total Revenue</h3>
              <p style="font-size: 1.8rem; font-weight: bold; color: #facc15; margin-top: 10px;">₱<?= number_format($total_revenue, 2) ?></p>
            </div>
          </div>
        </section>

      <?php elseif ($active_tab === 'site_controls'): ?>
        <section class="admin-panel">
          <h2>Blackout Switch</h2>
          <p class="site-controls-intro">
            Initiates Protocol "CONTENT VEIL" for Development Hiccups.
          </p>

          <?php if ($message): ?><p style="color: #0ADDEE; margin-bottom: 15px;"><?= htmlspecialchars($message) ?></p><?php endif; ?>
          <?php if ($error): ?><p style="color: #f87171; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

          <h3 style="margin-bottom: 16px; color: #0adde0;">Category: Pages</h3>

          <!-- TOGGLE ALL PAGES -->
          <form method="post" id="toggle-all-form">
            <input type="hidden" name="action" value="toggle_all_pages">
            <input type="hidden" name="hidden" id="toggle-all-hidden-input" value="<?= $all_pages_hidden ? '0' : '1' ?>">
            <div class="blackout-master-row">
              <div class="blackout-label">
                <strong>Hide All Pages</strong>
                <span>Puts every page into blackout at once.</span>
              </div>
              <label class="toggle-switch master">
                <input type="checkbox"
                       <?= $all_pages_hidden ? 'checked' : '' ?>
                       onchange="
                         document.getElementById('toggle-all-hidden-input').value = this.checked ? '1' : '0';
                         document.getElementById('toggle-all-form').submit();
                       ">
                <span class="toggle-slider"></span>
              </label>
            </div>
          </form>

          <!-- PER-PAGE TOGGLES -->
          <div class="page-visibility-list" style="margin-bottom: 36px;">
            <?php foreach ($controllable_pages as $key => $label):
              $is_hidden = $page_visibility[$key] ?? false;
            ?>
              <form method="post" class="page-visibility-row <?= $is_hidden ? 'is-hidden' : '' ?>" id="toggle-form-<?= $key ?>">
                <input type="hidden" name="action" value="toggle_page_visibility">
                <input type="hidden" name="page_key" value="<?= htmlspecialchars($key) ?>">
                <input type="hidden" name="hidden" id="toggle-hidden-input-<?= $key ?>" value="<?= $is_hidden ? '0' : '1' ?>">
                <div>
                  <span class="page-name"><?= htmlspecialchars($label) ?></span>
                  <span class="page-status"><?= $is_hidden ? 'Currently hidden from visitors' : 'Currently visible' ?></span>
                </div>
                <label class="toggle-switch">
                  <input type="checkbox"
                         <?= $is_hidden ? 'checked' : '' ?>
                         onchange="
                           document.getElementById('toggle-hidden-input-<?= $key ?>').value = this.checked ? '1' : '0';
                           document.getElementById('toggle-form-<?= $key ?>').submit();
                         ">
                  <span class="toggle-slider"></span>
                </label>
              </form>
            <?php endforeach; ?>
          </div>

          <h3 style="margin-bottom: 16px; color: #0adde0;">Category: Modals</h3>

          <!-- TOGGLE ALL MODALS -->
          <form method="post" id="toggle-all-modals-form">
            <input type="hidden" name="action" value="toggle_all_modals">
            <input type="hidden" name="hidden" id="toggle-all-modals-hidden-input" value="<?= $all_modals_hidden ? '0' : '1' ?>">
            <div class="blackout-master-row">
              <div class="blackout-label">
                <strong>Hide All Modals</strong>
                <span>Puts every modal into blackout at once.</span>
              </div>
              <label class="toggle-switch master">
                <input type="checkbox"
                       <?= $all_modals_hidden ? 'checked' : '' ?>
                       onchange="
                         document.getElementById('toggle-all-modals-hidden-input').value = this.checked ? '1' : '0';
                         document.getElementById('toggle-all-modals-form').submit();
                       ">
                <span class="toggle-slider"></span>
              </label>
            </div>
          </form>

          <!-- PER-MODAL TOGGLES -->
          <div class="page-visibility-list">
            <?php foreach ($controllable_modals as $key => $label):
              $is_hidden = $modal_visibility[$key] ?? false;
            ?>
              <form method="post" class="page-visibility-row <?= $is_hidden ? 'is-hidden' : '' ?>" id="toggle-form-<?= $key ?>">
                <input type="hidden" name="action" value="toggle_modal_visibility">
                <input type="hidden" name="modal_key" value="<?= htmlspecialchars($key) ?>">
                <input type="hidden" name="hidden" id="toggle-hidden-input-<?= $key ?>" value="<?= $is_hidden ? '0' : '1' ?>">
                <div>
                  <span class="page-name"><?= htmlspecialchars($label) ?></span>
                  <span class="page-status"><?= $is_hidden ? 'Currently hidden' : 'Currently visible' ?></span>
                </div>
                <label class="toggle-switch">
                  <input type="checkbox"
                         <?= $is_hidden ? 'checked' : '' ?>
                         onchange="
                           document.getElementById('toggle-hidden-input-<?= $key ?>').value = this.checked ? '1' : '0';
                           document.getElementById('toggle-form-<?= $key ?>').submit();
                         ">
                  <span class="toggle-slider"></span>
                </label>
              </form>
            <?php endforeach; ?>
          </div>
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

      <?php elseif ($active_tab === 'support'): ?>
        <section class="admin-panel">
          <h2>Customer Support Chat</h2>
          <?php if ($message): ?><p style="color: #0ADDEE; margin-bottom: 15px;"><?= htmlspecialchars($message) ?></p><?php endif; ?>
          <?php if ($error): ?><p style="color: #f87171; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

          <div class="support-chat-layout">
            <aside class="support-contacts-list">
              <?php if (empty($all_conversations)): ?>
                <p class="admin-panel-placeholder">No conversations yet.</p>
              <?php else: ?>
                <?php foreach ($all_conversations as $conv): ?>
                  <a href="?tab=support&conversation_id=<?= $conv['conversation_id'] ?>"
                     class="support-contact-row <?= $selected_conversation_id === (int) $conv['conversation_id'] ? 'active' : '' ?>">
                    <div class="support-contact-top">
                      <span class="support-contact-name"><?= htmlspecialchars(conversation_display_name($conv)) ?></span>
                      <span class="status-badge support-status-<?= htmlspecialchars($conv['status']) ?>">
                        <?= htmlspecialchars(chat_status_label($conv['status'])) ?>
                      </span>
                    </div>
                    <p class="support-contact-preview"><?= htmlspecialchars(mb_strimwidth($conv['last_message'] ?? '', 0, 60, '...')) ?></p>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </aside>

            <div class="support-chat-panel">
              <?php if (!$selected_conversation): ?>
                <p class="admin-panel-placeholder">Select a conversation to view it.</p>
              <?php else: ?>
                <div class="support-chat-header">
                  <strong><?= htmlspecialchars(conversation_display_name($selected_conversation)) ?></strong>
                  <span class="status-badge support-status-<?= htmlspecialchars($selected_conversation['status']) ?>">
                    <?= htmlspecialchars(chat_status_label($selected_conversation['status'])) ?>
                  </span>
                </div>

                <div class="support-chat-messages"
                     id="supportChatMessages"
                     data-conversation-id="<?= $selected_conversation['conversation_id'] ?>"
                     data-last-id="<?= $selected_messages ? end($selected_messages)['message_id'] : 0 ?>">
                  <?php foreach ($selected_messages as $msg): ?>
                    <div class="support-msg support-msg-<?= htmlspecialchars($msg['sender_type']) ?>" data-message-id="<?= $msg['message_id'] ?>">
                      <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    </div>
                  <?php endforeach; ?>
                </div>

                <form method="post" class="support-reply-form">
                  <input type="hidden" name="action" value="send_admin_reply">
                  <input type="hidden" name="conversation_id" value="<?= $selected_conversation['conversation_id'] ?>">
                  <textarea name="message" rows="2" placeholder="Type a reply..." required></textarea>
                  <button type="submit" class="btn btn-primary"><?php icon('send'); ?></button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </section>

      <?php else: ?>
        <section class="admin-panel">
          <h2><?= htmlspecialchars($admin_tabs[$active_tab]) ?></h2>
          <p class="admin-panel-placeholder"><?= htmlspecialchars($admin_tabs[$active_tab]) ?> panel layout will go here.</p>
        </section>
      <?php endif; ?>
    </main>
  </div>
  <script src="assets/js/admin-chat.js"></script>
</body>
</html>