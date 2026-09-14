<?php
/**
 * Talks to a locally-running Ollama server (same machine as XAMPP).
 * API reference: https://github.com/ollama/ollama/blob/main/docs/api.md
 */

define('OLLAMA_API_URL', 'http://localhost:11434/api/chat');
define('OLLAMA_MODEL', 'mannix/llama3.1-8b-lexi:latest');

const OLLAMA_SYSTEM_PROMPT = <<<PROMPT
You are the support assistant for TimosaTech, a store that sells computer
hardware and also offers printing services, hardware repair, networking/IT
services, and online consultations. Answer questions about products,
services, store hours, and general how-things-work questions concisely and
helpfully, in a few sentences at most. If you don't know something specific
(exact stock, an order's status, or a price you're not sure of), say so
honestly instead of guessing, and mention the visitor can ask to speak
with a human for that.
PROMPT;

// Sends the conversation so far (array of ['role' => 'user'|'assistant',
// 'content' => ...]) to Ollama and returns the assistant's reply text.
// Returns null on any failure (server not running, timeout, bad response)
// so callers can fall back gracefully instead of crashing the chat.
function get_ollama_reply(array $history): ?string {
    $messages = array_merge(
        [['role' => 'system', 'content' => OLLAMA_SYSTEM_PROMPT]],
        $history
    );

    $payload = json_encode([
        'model'    => OLLAMA_MODEL,
        'messages' => $messages,
        'stream'   => false,
    ]);

    $ch = curl_init(OLLAMA_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curl_error) {
        error_log("Ollama request failed: $curl_error");
        return null;
    }

    $data = json_decode($response, true);
    return $data['message']['content'] ?? null;
}