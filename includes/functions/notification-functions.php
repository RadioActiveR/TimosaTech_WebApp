<?php
/**
 * Notification system — a single shared table serves two audiences:
 *   - Customers ('user'): scoped to their own u_id (chat replies, order
 *     status updates).
 *   - Admins ('admin'): a shared feed visible to any admin account (new
 *     orders, conversations that need a human) — u_id is NULL for these
 *     rows since they aren't tied to one specific account.
 *
 * Every notification carries an absolute `link` (starting with
 * /TimosaTech/...) so the frontend can navigate to it directly regardless
 * of which page's directory depth it's clicked from.
 */

function create_notification(
    PDO $pdo,
    string $recipient_type,
    ?string $u_id,
    string $type,
    string $title,
    ?string $message = null,
    ?string $link = null,
    ?string $related_id = null
): int {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (recipient_type, u_id, type, title, message, link, related_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$recipient_type, $u_id, $type, $title, $message, $link, $related_id]);
    return (int) $pdo->lastInsertId();
}

function get_user_notifications(PDO $pdo, string $u_id, int $limit = 20): array {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications
        WHERE recipient_type = 'user' AND u_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $u_id, PDO::PARAM_STR);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_admin_notifications(PDO $pdo, int $limit = 20): array {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications
        WHERE recipient_type = 'admin'
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_unread_count_for_user(PDO $pdo, string $u_id): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM notifications
        WHERE recipient_type = 'user' AND u_id = ? AND is_read = 0
    ");
    $stmt->execute([$u_id]);
    return (int) $stmt->fetchColumn();
}

// Narrower count for one notification type — used to badge the floating
// chat widget with unread chat replies specifically, separate from the
// general notification bell's total.
function get_unread_count_for_user_by_type(PDO $pdo, string $u_id, string $type): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM notifications
        WHERE recipient_type = 'user' AND u_id = ? AND type = ? AND is_read = 0
    ");
    $stmt->execute([$u_id, $type]);
    return (int) $stmt->fetchColumn();
}

function get_unread_count_for_admin(PDO $pdo): int {
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM notifications
        WHERE recipient_type = 'admin' AND is_read = 0
    ");
    return (int) $stmt->fetchColumn();
}

function mark_notification_read(PDO $pdo, int $notification_id): bool {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
    return $stmt->execute([$notification_id]);
}

function mark_all_notifications_read_for_user(PDO $pdo, string $u_id): bool {
    $stmt = $pdo->prepare("
        UPDATE notifications SET is_read = 1
        WHERE recipient_type = 'user' AND u_id = ? AND is_read = 0
    ");
    return $stmt->execute([$u_id]);
}

// Used when the visitor opens the floating chat widget — reading the
// conversation there should clear just the chat badge, not unrelated
// order-update notifications sitting in the bell dropdown.
function mark_notifications_read_by_type(PDO $pdo, string $u_id, string $type): bool {
    $stmt = $pdo->prepare("
        UPDATE notifications SET is_read = 1
        WHERE recipient_type = 'user' AND u_id = ? AND type = ? AND is_read = 0
    ");
    return $stmt->execute([$u_id, $type]);
}

function mark_all_notifications_read_for_admin(PDO $pdo): bool {
    $stmt = $pdo->prepare("
        UPDATE notifications SET is_read = 1
        WHERE recipient_type = 'admin' AND is_read = 0
    ");
    return $stmt->execute();
}

// Marks any "needs a human" notification tied to a specific conversation
// as read — called whenever an admin opens that conversation, so the
// bell clears the same way viewing an inbox thread would, without
// requiring an explicit click on the notification itself.
function mark_admin_notifications_read_for_conversation(PDO $pdo, int $conversation_id): bool {
    $stmt = $pdo->prepare("
        UPDATE notifications SET is_read = 1
        WHERE recipient_type = 'admin' AND type = 'chat_needs_human'
          AND related_id = ? AND is_read = 0
    ");
    return $stmt->execute([(string) $conversation_id]);
}

// ---------------------------------------------------------------------
// Event-specific helpers — called from the code paths that already own
// each event, so the notification logic lives next to (and can't drift
// from) the thing it's describing.
// ---------------------------------------------------------------------

// Notifies the customer that an admin has replied in their live chat
// conversation. Skipped for guest visitors (no account/u_id to notify) —
// they only see it live in the widget itself while it's open.
function notify_customer_of_chat_reply(PDO $pdo, array $conversation, string $reply_message): void {
    if (empty($conversation['u_id'])) {
        return;
    }
    create_notification(
        $pdo,
        'user',
        $conversation['u_id'],
        'chat_reply',
        'New reply from support',
        mb_strimwidth($reply_message, 0, 100, '...'),
        '/TimosaTech/pages/homepage.php',
        (string) $conversation['conversation_id']
    );
}

// Notifies admins that a conversation now needs a human. Called from
// set_conversation_status() whenever that specific transition happens
// (see chat-functions.php), so every code path that escalates a
// conversation — present or future — picks this up automatically rather
// than each caller having to remember to notify.
function notify_admins_conversation_needs_human(PDO $pdo, array $conversation): void {
    create_notification(
        $pdo,
        'admin',
        null,
        'chat_needs_human',
        'Conversation needs attention',
        conversation_display_name($conversation) . ' is waiting for a human reply.',
        '/TimosaTech/admin/admin-portal.php?tab=support&conversation_id=' . $conversation['conversation_id'],
        (string) $conversation['conversation_id']
    );
}

// Notifies admins that a new order has just been placed.
function notify_admins_new_order(PDO $pdo, string $order_id, string $recipient_name, float $total_amount): void {
    create_notification(
        $pdo,
        'admin',
        null,
        'new_order',
        'New order placed',
        $recipient_name . ' — ₱' . number_format($total_amount, 2),
        '/TimosaTech/admin/admin-portal.php?tab=orders&order_id=' . urlencode($order_id),
        $order_id
    );
}

// Notifies the customer that their order's status has changed.
function notify_customer_of_order_status(PDO $pdo, string $order_id, string $u_id, string $new_status): void {
    $labels = [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'shipped'    => 'Shipped',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
    ];
    $label = $labels[$new_status] ?? ucfirst($new_status);

    create_notification(
        $pdo,
        'user',
        $u_id,
        'order_status',
        'Order update',
        'Your order #' . $order_id . ' is now ' . $label . '.',
        '/TimosaTech/pages/profile.php',
        $order_id
    );
}