<?php
/**
 * Admin-only AJAX endpoint for the header notification bell: new orders
 * and conversations that need a human. Shared across all admin accounts
 * (see notification-functions.php) rather than scoped to one u_id.
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/functions/notification-functions.php';

if (!isset($_SESSION['u_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'forbidden']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {
    case 'mark_read':
        $notification_id = intval($_POST['notification_id'] ?? 0);
        if ($notification_id > 0) {
            mark_notification_read($pdo, $notification_id);
        }
        break;

    case 'mark_all_read':
        mark_all_notifications_read_for_admin($pdo);
        break;

    case 'list':
    default:
        break;
}

echo json_encode([
    'success'       => true,
    'unread_count'  => get_unread_count_for_admin($pdo),
    'notifications' => get_admin_notifications($pdo),
]);