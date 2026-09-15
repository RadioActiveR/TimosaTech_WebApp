<?php
/**
 * Shared chat helpers — conversation lifecycle + messages. Used by the
 * public-facing chat widget (includes/handlers/chat-handler.php) and the
 * admin dashboard's Customer Support Chat tab alike.
 */

// Simple keyword check for "this visitor wants a human" — deliberately not
// relying on the LLM to self-report this, since a plain keyword match is
// more predictable to test and debug.
const CHAT_HUMAN_KEYWORDS = [
    'human', 'agent', 'real person', 'representative',
    'talk to someone', 'speak to someone', 'talk to a person',
];

function chat_wants_human(string $message): bool {
    $lower = strtolower($message);
    foreach (CHAT_HUMAN_KEYWORDS as $kw) {
        if (str_contains($lower, $kw)) {
            return true;
        }
    }
    return false;
}

// Guests are identified by a random token stored in their PHP session —
// no account required to chat. Logged-in visitors are matched by u_id
// instead (see get_or_create_conversation()).
function get_or_create_guest_token(): string {
    if (empty($_SESSION['chat_guest_token'])) {
        $_SESSION['chat_guest_token'] = 'guest_' . bin2hex(random_bytes(12));
    }
    return $_SESSION['chat_guest_token'];
}

// Finds this visitor's most recent still-open conversation, or starts a
// new one. Exactly one of $u_id / $guest_token should be non-null.
function get_or_create_conversation(PDO $pdo, ?string $u_id, ?string $guest_token): array {
    if ($u_id) {
        $stmt = $pdo->prepare("
            SELECT * FROM chat_conversations
            WHERE u_id = ? AND status != 'closed'
            ORDER BY conversation_id DESC LIMIT 1
        ");
        $stmt->execute([$u_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM chat_conversations
            WHERE guest_token = ? AND status != 'closed'
            ORDER BY conversation_id DESC LIMIT 1
        ");
        $stmt->execute([$guest_token]);
    }
    $conversation = $stmt->fetch();
    if ($conversation) {
        return $conversation;
    }

    $stmt = $pdo->prepare("
        INSERT INTO chat_conversations (u_id, guest_token, status)
        VALUES (?, ?, 'bot')
    ");
    $stmt->execute([$u_id, $u_id ? null : $guest_token]);
    $conversation_id = (int) $pdo->lastInsertId();

    return get_conversation($pdo, $conversation_id);
}

function get_conversation(PDO $pdo, int $conversation_id): ?array {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username AS user_username
        FROM chat_conversations c
        LEFT JOIN users u ON c.u_id = u.u_id
        WHERE c.conversation_id = ?
    ");
    $stmt->execute([$conversation_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function add_chat_message(PDO $pdo, int $conversation_id, string $sender_type, ?string $sender_id, string $message): int {
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$conversation_id, $sender_type, $sender_id, $message]);

    $update = $pdo->prepare("UPDATE chat_conversations SET last_message_at = NOW() WHERE conversation_id = ?");
    $update->execute([$conversation_id]);

    return (int) $pdo->lastInsertId();
}

// Pass $after_id to only get messages newer than a given message — used
// for polling. Omit (or 0) to get the full thread.
function get_conversation_messages(PDO $pdo, int $conversation_id, int $after_id = 0): array {
    $stmt = $pdo->prepare("
        SELECT * FROM chat_messages
        WHERE conversation_id = ? AND message_id > ?
        ORDER BY message_id ASC
    ");
    $stmt->execute([$conversation_id, $after_id]);
    return $stmt->fetchAll();
}

function set_conversation_status(PDO $pdo, int $conversation_id, string $status): void {
    $stmt = $pdo->prepare("UPDATE chat_conversations SET status = ? WHERE conversation_id = ?");
    $stmt->execute([$status, $conversation_id]);
}

// Called when an admin sends a reply: claims the conversation for that
// admin and marks it actively handled by a human.
function assign_conversation(PDO $pdo, int $conversation_id, string $admin_id): void {
    $stmt = $pdo->prepare("
        UPDATE chat_conversations
        SET status = 'active_human', assigned_admin_id = ?
        WHERE conversation_id = ?
    ");
    $stmt->execute([$admin_id, $conversation_id]);
}

// Permanently removes a conversation (and, via ON DELETE CASCADE on
// chat_messages, all of its messages) — used by the admin dashboard for
// cleaning up stale guest threads. Returns true if a row was deleted.
function delete_conversation(PDO $pdo, int $conversation_id): bool {
    $stmt = $pdo->prepare("DELETE FROM chat_conversations WHERE conversation_id = ?");
    $stmt->execute([$conversation_id]);
    return $stmt->rowCount() > 0;
}

// For the admin dashboard's contact list: every conversation with a
// preview of its last message, newest activity first.
function get_all_conversations_for_admin(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT c.*,
               u.username AS user_username,
               (SELECT message FROM chat_messages m
                WHERE m.conversation_id = c.conversation_id
                ORDER BY m.message_id DESC LIMIT 1) AS last_message
        FROM chat_conversations c
        LEFT JOIN users u ON c.u_id = u.u_id
        ORDER BY c.last_message_at DESC
    ");
    return $stmt->fetchAll();
}

// Human-friendly label for a conversation in the admin contact list.
function conversation_display_name(array $conversation): string {
    if (!empty($conversation['user_username'])) {
        return $conversation['user_username'];
    }
    if (!empty($conversation['visitor_name'])) {
        return $conversation['visitor_name'];
    }
    return 'Guest ' . substr($conversation['guest_token'] ?? 'unknown', -6);
}

function chat_status_label(string $status): string {
    $labels = [
        'bot'           => 'Bot',
        'pending_human' => 'Needs You',
        'active_human'  => 'Active',
        'closed'        => 'Closed',
    ];
    return $labels[$status] ?? $status;
}