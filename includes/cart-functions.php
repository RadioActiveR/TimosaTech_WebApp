<?php
/* INFO: Cart helper functions, shared by cart-handler.php (AJAX) and
 * checkout.php / order-handler.php (server-side re-validation at checkout).
 * All quantities are clamped to live product stock here so the cart can
 * never silently hold more than what's actually available.
 */

// Returns every cart row for a user, joined to its product + image.
function get_cart_items(PDO $pdo, string $u_id): array {
    $stmt = $pdo->prepare("
        SELECT ci.cart_item_id, ci.product_id, ci.quantity,
               p.name, p.price, p.stock, p.category,
               i.image_data, i.mime_type
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        LEFT JOIN images i ON p.image_id = i.image_id
        WHERE ci.u_id = ?
        ORDER BY ci.added_at DESC
    ");
    $stmt->execute([$u_id]);
    return $stmt->fetchAll();
}

// Returns only the cart rows matching the given cart_item_ids — scoped to
// u_id so a user can never pull in someone else's cart row by ID-guessing.
// Used by checkout.php / order-handler.php to fetch just the items the
// user checked off in the cart modal before clicking "Order Now".
function get_cart_items_by_ids(PDO $pdo, string $u_id, array $cart_item_ids): array {
    $cart_item_ids = array_values(array_unique(array_map('intval', $cart_item_ids)));
    $cart_item_ids = array_filter($cart_item_ids, fn($id) => $id > 0);
    if (empty($cart_item_ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($cart_item_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT ci.cart_item_id, ci.product_id, ci.quantity,
               p.name, p.price, p.stock, p.category,
               i.image_data, i.mime_type
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        LEFT JOIN images i ON p.image_id = i.image_id
        WHERE ci.u_id = ? AND ci.cart_item_id IN ($placeholders)
        ORDER BY ci.added_at DESC
    ");
    $stmt->execute(array_merge([$u_id], $cart_item_ids));
    return $stmt->fetchAll();
}

// Central place that decides "which cart items is this checkout for".
// If the user picked a subset in the cart modal (stored in
// $_SESSION['checkout_selected_items'] by checkout.php), use exactly those —
// re-validated against the live cart so stale/removed IDs can't leak through.
// Otherwise (direct nav to checkout.php, no selection made) fall back to the
// whole cart, same as the old behavior.
// Requires session_start() to already have been called by the caller.
function get_checkout_cart_items(PDO $pdo, string $u_id): array {
    if (!empty($_SESSION['checkout_selected_items'])) {
        $items = get_cart_items_by_ids($pdo, $u_id, $_SESSION['checkout_selected_items']);
        if (!empty($items)) {
            return $items;
        }
    }
    return get_cart_items($pdo, $u_id);
}

// Total quantity across all cart rows (used for the navbar badge).
function get_cart_count(PDO $pdo, string $u_id): int {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE u_id = ?");
    $stmt->execute([$u_id]);
    return (int) $stmt->fetchColumn();
}

// Adds a product to the cart, or increments quantity if it's already there.
function add_to_cart(PDO $pdo, string $u_id, int $product_id, int $qty = 1): array {
    $qty = max(1, $qty);

    $stmt = $pdo->prepare("SELECT stock FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product) {
        return ['success' => false, 'error' => 'Product not found.'];
    }

    $stmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE u_id = ? AND product_id = ?");
    $stmt->execute([$u_id, $product_id]);
    $existing_qty = (int) ($stmt->fetchColumn() ?: 0);

    $new_qty = min($existing_qty + $qty, (int) $product['stock']);
    if ($new_qty < 1) {
        return ['success' => false, 'error' => 'This item is out of stock.'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO cart_items (u_id, product_id, quantity) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = ?
    ");
    $stmt->execute([$u_id, $product_id, $new_qty, $new_qty]);

    return ['success' => true];
}

// Sets a cart row to an exact quantity (used by the +/- controls in the cart modal).
// Deletes the row if quantity drops to 0 or below.
function update_cart_item(PDO $pdo, string $u_id, int $cart_item_id, int $qty): array {
    $stmt = $pdo->prepare("
        SELECT ci.cart_item_id, p.stock
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_item_id = ? AND ci.u_id = ?
    ");
    $stmt->execute([$cart_item_id, $u_id]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['success' => false, 'error' => 'Item not found in your cart.'];
    }

    if ($qty <= 0) {
        return remove_cart_item($pdo, $u_id, $cart_item_id);
    }

    $qty = min($qty, (int) $row['stock']);

    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND u_id = ?");
    $stmt->execute([$qty, $cart_item_id, $u_id]);

    return ['success' => true];
}

function remove_cart_item(PDO $pdo, string $u_id, int $cart_item_id): array {
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_item_id = ? AND u_id = ?");
    $stmt->execute([$cart_item_id, $u_id]);
    return ['success' => true];
}

function clear_cart(PDO $pdo, string $u_id): void {
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE u_id = ?");
    $stmt->execute([$u_id]);
}