<?php
/**
 * Shared chat helpers — conversation lifecycle + messages. Used by the
 * public-facing chat widget (includes/handlers/chat-handler.php) and the
 * admin dashboard's Customer Support Chat tab alike.
 */

require_once __DIR__ . '/notification-functions.php';

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

// Short, unambiguous "yes" replies — deliberately a small closed list
// rather than anything fuzzier, so this only fires on a genuine plain
// affirmative and not on, say, "yes but how much does it cost".
const CHAT_AFFIRMATIVE_REPLIES = [
    'yes', 'yeah', 'yep', 'yup', 'sure', 'please', 'ok', 'okay', 'k',
    'yes please', 'please do', 'go ahead', 'sounds good', 'that works',
    'that would be great', 'id like that', "i'd like that",
];

function chat_is_affirmative(string $message): bool {
    // Trims trailing punctuation so "Yes!" / "yes." / "Sure," all still match.
    $normalized = strtolower(trim($message, " \t\n\r\0\x0B.!,"));
    return in_array($normalized, CHAT_AFFIRMATIVE_REPLIES, true);
}

// True when the bot's own last message was itself offering to bring in a
// human — e.g. "Would you like me to get a human for you?". Combined with
// chat_is_affirmative(), this catches a visitor answering "yes please" to
// that offer, which contains none of CHAT_HUMAN_KEYWORDS on its own and
// would otherwise just get treated as an ordinary question for the LLM.
function chat_bot_offered_human(?string $last_bot_message): bool {
    if (!$last_bot_message) {
        return false;
    }

    // Only the LAST sentence — the actual live question posed to the
    // visitor — is checked, not the whole message. Checking the whole
    // message caused a false positive: an earlier sentence mentioning
    // "human" (e.g. "a human representative is still here to help") plus
    // an unrelated question mark later in the message (e.g. "What's going
    // on?") was enough to match, even though nothing in the message was
    // actually offering to connect one.
    $sentences = preg_split('/(?<=[.?!])\s+/', trim($last_bot_message)) ?: [$last_bot_message];
    $last_sentence = strtolower(end($sentences));

    $mentions_human = str_contains($last_sentence, 'human')
        || str_contains($last_sentence, 'representative')
        || str_contains($last_sentence, 'agent');

    $is_offer_phrasing = str_contains($last_sentence, 'would you like')
        || str_contains($last_sentence, 'do you want')
        || str_contains($last_sentence, 'want me to')
        || str_contains($last_sentence, 'shall i')
        || str_contains($last_sentence, 'should i');

    return $mentions_human && $is_offer_phrasing;
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

// Looks up this visitor's most recent still-open conversation without
// creating one. Returns null if they don't have one yet — e.g. a guest
// who has opened the chat widget or the Contact page but never actually
// sent a message. Exactly one of $u_id / $guest_token should be non-null.
function find_conversation(PDO $pdo, ?string $u_id, ?string $guest_token): ?array {
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
    return $conversation ?: null;
}

// Finds this visitor's most recent still-open conversation, or starts a
// new one. Exactly one of $u_id / $guest_token should be non-null. Only
// call this at the moment a message is actually being sent — anywhere
// that shouldn't create a row just from a visitor opening the chat
// widget (see chat-handler.php's "history" action) should call
// find_conversation() instead.
function get_or_create_conversation(PDO $pdo, ?string $u_id, ?string $guest_token): array {
    $conversation = find_conversation($pdo, $u_id, $guest_token);
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

    $message_id = (int) $pdo->lastInsertId();

    // Confirmed via browser console logging: lastInsertId() has come back
    // 0 here even though the row was inserted successfully (visible a
    // moment later via polling, under its real id). 0 is never a
    // legitimate id for a real AUTO_INCREMENT row, so treat it as a
    // signal to look the row up directly instead of trusting it blindly.
    // Reporting id 0 back to the client is what caused the visible
    // duplicate: the client's "last seen id" tracker never advances past
    // the previous message (since 0 doesn't beat it), so the very next
    // poll re-fetches this same row under its real id and renders a
    // second, genuinely separate bubble for it.
    if ($message_id === 0) {
        $lookup = $pdo->prepare("
            SELECT message_id FROM chat_messages
            WHERE conversation_id = ? AND sender_type = ? AND message = ?
            ORDER BY message_id DESC LIMIT 1
        ");
        $lookup->execute([$conversation_id, $sender_type, $message]);
        $message_id = (int) ($lookup->fetchColumn() ?: 0);
    }

    $update = $pdo->prepare("UPDATE chat_conversations SET last_message_at = NOW() WHERE conversation_id = ?");
    $update->execute([$conversation_id]);

    return $message_id;
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
    $stmt = $pdo->prepare("SELECT status FROM chat_conversations WHERE conversation_id = ?");
    $stmt->execute([$conversation_id]);
    $old_status = $stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE chat_conversations SET status = ? WHERE conversation_id = ?");
    $stmt->execute([$status, $conversation_id]);

    // Notify admins the first time a conversation flips to "needs a
    // human" — not on every message after that, and not if it was
    // already in that state (e.g. the visitor sends several messages in
    // a row while waiting). Centralizing this here means every code path
    // that escalates a conversation, now or in the future, picks it up
    // automatically instead of each caller having to remember to notify.
    if ($status === 'pending_human' && $old_status !== 'pending_human') {
        $conversation = get_conversation($pdo, $conversation_id);
        if ($conversation) {
            notify_admins_conversation_needs_human($pdo, $conversation);
        }
    }
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