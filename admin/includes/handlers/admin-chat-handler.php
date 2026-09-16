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
$after_id        = intval($_GET['after_id'] ?? 0);

// Sending a reply via AJAX so the page doesn't reload. The equivalent
// form POST to admin-portal.php is kept as-is and still works as a
// no-JS fallback — this just intercepts the common case.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_conversation_id = intval($_POST['conversation_id'] ?? 0);
    $reply_message        = trim($_POST['message'] ?? '');

    if ($post_conversation_id <= 0 || $reply_message === '') {
        echo json_encode(['success' => false, 'error' => 'missing_fields']);
        exit;
    }

    assign_conversation($pdo, $post_conversation_id, $_SESSION['u_id']);
    $message_id = add_chat_message($pdo, $post_conversation_id, 'admin', $_SESSION['u_id'], $reply_message);

    $conversation = get_conversation($pdo, $post_conversation_id);
    if ($conversation) {
        notify_customer_of_chat_reply($pdo, $conversation, $reply_message);
    }

    echo json_encode([
        'success' => true,
        'message' => [
            'message_id'  => $message_id,
            'sender_type' => 'admin',
            'message'     => $reply_message,
        ],
    ]);
    exit;
}

// The conversation list is always returned so the sidebar stays live even
// when no thread is open. Messages are only included when a specific
// conversation is being viewed.
$conversations = [];
foreach (get_all_conversations_for_admin($pdo) as $conv) {
    $conversations[] = [
        'conversation_id' => (int) $conv['conversation_id'],
        'display_name'    => conversation_display_name($conv),
        'status'          => $conv['status'],
        'status_label'    => chat_status_label($conv['status']),
        'preview'         => mb_strimwidth($conv['last_message'] ?? '', 0, 60, '...'),
        'last_message_at' => $conv['last_message_at'],
    ];
}

$response = [
    'success'       => true,
    'conversations' => $conversations,
];

if ($conversation_id) {
    $response['messages'] = get_conversation_messages($pdo, $conversation_id, $after_id);
    mark_admin_notifications_read_for_conversation($pdo, $conversation_id);
}

echo json_encode($response);