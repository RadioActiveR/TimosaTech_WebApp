<?php
/**
 * JSON AJAX endpoint for the public chat widget (assets/js/chat-widget.js).
 * Open to guests as well as logged-in users — a guest is identified by a
 * random token stored in their session (see chat-functions.php).
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions/chat-functions.php';
require_once __DIR__ . '/../functions/ollama-functions.php';

$u_id = $_SESSION['u_id'] ?? null;
$guest_token = $u_id ? null : get_or_create_guest_token();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Confirms $conversation actually belongs to the current visitor, so a
// guessed conversation_id in the URL can't leak someone else's chat.
function chat_conversation_belongs_to(?array $conversation, ?string $u_id, ?string $guest_token): bool {
    if (!$conversation) {
        return false;
    }
    if ($u_id) {
        return $conversation['u_id'] === $u_id;
    }
    return $conversation['guest_token'] === $guest_token;
}

switch ($action) {
    case 'history':
        $conversation = get_or_create_conversation($pdo, $u_id, $guest_token);
        echo json_encode([
            'success'         => true,
            'conversation_id' => $conversation['conversation_id'],
            'status'          => $conversation['status'],
            'messages'        => get_conversation_messages($pdo, $conversation['conversation_id']),
        ]);
        break;

    case 'poll':
        $conversation_id = intval($_GET['conversation_id'] ?? 0);
        $after_id        = intval($_GET['after_id'] ?? 0);
        $conversation    = get_conversation($pdo, $conversation_id);

        if (!chat_conversation_belongs_to($conversation, $u_id, $guest_token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'not_found']);
            break;
        }

        echo json_encode([
            'success'  => true,
            'status'   => $conversation['status'],
            'messages' => get_conversation_messages($pdo, $conversation_id, $after_id),
        ]);
        break;

    case 'send':
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            echo json_encode(['success' => false, 'error' => 'empty_message']);
            break;
        }

        $conversation    = get_or_create_conversation($pdo, $u_id, $guest_token);
        $conversation_id = (int) $conversation['conversation_id'];

        // Captured before inserting the new visitor message, so we can
        // tell whether the bot itself just asked "want a human?" — a
        // plain "yes please" reply contains none of CHAT_HUMAN_KEYWORDS
        // on its own, so without this context it would silently fall
        // through to an ordinary LLM reply instead of escalating.
        $last_bot_message = null;
        foreach (array_reverse(get_conversation_messages($pdo, $conversation_id)) as $row) {
            if ($row['sender_type'] === 'bot') {
                $last_bot_message = $row['message'];
                break;
            }
        }

        add_chat_message($pdo, $conversation_id, 'visitor', $u_id, $message);

        $wants_human = chat_wants_human($message)
            || (chat_is_affirmative($message) && chat_bot_offered_human($last_bot_message));

        // Only the bot auto-replies while status is still 'bot'. Once a
        // human has been requested (or is already handling it), the admin
        // dashboard takes over and the bot stays quiet.
        if ($conversation['status'] === 'bot') {
            if ($wants_human) {
                set_conversation_status($pdo, $conversation_id, 'pending_human');
                add_chat_message(
                    $pdo, $conversation_id, 'bot', null,
                    "Got it — I've flagged this for our team. Someone will reply here as soon as they're available."
                );
            } else {
                $history = array_map(
                    function ($row) {
                        $role = $row['sender_type'] === 'visitor' ? 'user' : 'assistant';
                        $content = $row['message'];
                        // Human admin replies get tagged distinctly in the
                        // transcript. Without this, once a conversation is
                        // handed back to the bot (Return to Bot), whatever
                        // a human said earlier is silently absorbed into
                        // the bot's own "assistant" history — the bot then
                        // reads it as something it said itself, and can
                        // elaborate on it as if a human is still present,
                        // with nothing to signal the handoff has ended.
                        if ($row['sender_type'] === 'admin') {
                            $content = "[Earlier in this conversation, a human support agent (not you) replied]: " . $content;
                        }
                        return ['role' => $role, 'content' => $content];
                    },
                    get_conversation_messages($pdo, $conversation_id)
                );

                $reply = get_ollama_reply($history)
                    ?? "Sorry, I'm having trouble responding right now. Want me to get a human instead?";

                add_chat_message($pdo, $conversation_id, 'bot', null, $reply);
            }
        }

        echo json_encode([
            'success'         => true,
            'conversation_id' => $conversation_id,
            'status'          => get_conversation($pdo, $conversation_id)['status'],
            'messages'        => get_conversation_messages($pdo, $conversation_id),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'unknown_action']);
}