<?php
/**
 * Customer-facing AJAX endpoint for the header notification bell and the
 * floating chat widget's unread badge. Session-gated to the logged-in
 * user's own u_id — never returns or touches another user's notifications.
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions/notification-functions.php';

if (!isset($_SESSION['u_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$u_id = $_SESSION['u_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {
    case 'mark_read':
        $notification_id = intval($_POST['notification_id'] ?? 0);
        if ($notification_id > 0) {
            mark_notification_read($pdo, $notification_id);
        }
        break;

    case 'mark_all_read':
        mark_all_notifications_read_for_user($pdo, $u_id);
        break;

    // Opening the chat widget counts as reading pending chat replies,
    // without touching unrelated order-update notifications.
    case 'mark_read_chat':
        mark_notifications_read_by_type($pdo, $u_id, 'chat_reply');
        break;

    case 'list':
    default:
        break;
}

echo json_encode([
    'success'       => true,
    'unread_count'  => get_unread_count_for_user($pdo, $u_id),
    'unread_chat'   => get_unread_count_for_user_by_type($pdo, $u_id, 'chat_reply'),
    'notifications' => get_user_notifications($pdo, $u_id),
]);