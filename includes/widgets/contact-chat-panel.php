<?php
/**
 * Larger, embedded chat panel for the Contact page. Same backend
 * (includes/handlers/chat-handler.php) and same conversation as the
 * floating widget — just a bigger inline surface instead of a small
 * floating bubble. The floating widget is suppressed on this page (see
 * components/header.php) so there's only one chat surface visible at once.
 */
?>
<div class="contact-chat-panel">
  <div class="contact-chat-header">
    <span>Live Chat</span>
    <span class="contact-chat-subtitle">Usually replies in a few seconds</span>
  </div>

  <div class="contact-chat-messages" id="contactChatMessages"></div>

  <form class="contact-chat-form" id="contactChatForm">
    <textarea id="contactChatInput" rows="1" placeholder="Type a message... (Enter to send, Shift+Enter for a new line)" autocomplete="off"></textarea>
    <button type="submit" class="btn btn-primary" aria-label="Send message">
      <?php icon('send'); ?>
    </button>
  </form>
</div>