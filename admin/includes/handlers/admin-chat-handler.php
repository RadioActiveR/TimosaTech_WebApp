<?php
/**
 * Admin-only AJAX endpoint: polls for new messages in an open conversation
 * thread on the Customer Support Chat tab. Sending a reply is a normal
 * form POST to admin-portal.php (consistent with the rest of the admin
 * dashboard) — this endpoint only handles the live "did anything new come
 * in" refresh while an admin has a thread open.
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/functions/chat-functions.php';

if (!isset($_SESSION['u_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'forbidden']);
    exit;
}

$conversation_id = intval($_GET['conversation_id'] ?? 0);
$after_id         = intval($_GET['after_id'] ?? 0);

if (!$conversation_id) {
    echo json_encode(['success' => false, 'error' => 'missing_conversation_id']);
    exit;
}

echo json_encode([
    'success'  => true,
    'messages' => get_conversation_messages($pdo, $conversation_id, $after_id),
]);