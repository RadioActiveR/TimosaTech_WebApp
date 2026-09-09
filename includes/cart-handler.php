<?php
/* INFO: JSON AJAX endpoint for the cart modal (js/cart.js).
 * Every action returns the full, freshly-queried cart state so the
 * frontend can just re-render rather than trying to keep two copies
 * of the cart in sync.
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cart-functions.php';

if (!isset($_SESSION['u_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$u_id   = $_SESSION['u_id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $result = add_to_cart($pdo, $u_id, intval($_POST['product_id'] ?? 0), intval($_POST['quantity'] ?? 1));
        break;

    case 'update':
        $result = update_cart_item($pdo, $u_id, intval($_POST['cart_item_id'] ?? 0), intval($_POST['quantity'] ?? 1));
        break;

    case 'remove':
        $result = remove_cart_item($pdo, $u_id, intval($_POST['cart_item_id'] ?? 0));
        break;

    case 'list':
    default:
        $result = ['success' => true];
        break;
}

$items = get_cart_items($pdo, $u_id);
$total = 0;
foreach ($items as &$item) {
    $item['subtotal']   = round($item['price'] * $item['quantity'], 2);
    $item['image_src']  = !empty($item['image_data'])
        ? 'data:' . ($item['mime_type'] ?: 'image/png') . ';base64,' . $item['image_data']
        : '../images/workstation-rig.png';
    unset($item['image_data'], $item['mime_type']);
    $total += $item['subtotal'];
}
unset($item);

echo json_encode(array_merge($result, [
    'items' => array_values($items),
    'total' => round($total, 2),
    'count' => (int) array_sum(array_column($items, 'quantity')),
]));