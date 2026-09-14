<?php
/**
 * Floating site-wide support chat widget. Local Ollama bot answers by
 * default; escalates to a human (picked up via the admin dashboard's
 * Customer Support Chat tab) the moment the visitor asks for one.
 * Available to guests and logged-in users alike — no login required.
 * Included from components/header.php on every non-admin page.
 */
?>
<div class="chat-widget" id="chatWidget">
  <button type="button" class="chat-widget-toggle" id="chatWidgetToggle" aria-label="Open support chat">
    <?php icon('chat'); ?>
  </button>

  <div class="chat-widget-panel" id="chatWidgetPanel">
    <div class="chat-widget-header">
      <span>TimosaTech Support</span>
      <button type="button" class="chat-widget-close" id="chatWidgetClose" aria-label="Close chat">&times;</button>
    </div>

    <div class="chat-widget-messages" id="chatWidgetMessages"></div>

    <form class="chat-widget-form" id="chatWidgetForm">
      <input type="text" id="chatWidgetInput" placeholder="Type a message..." autocomplete="off">
      <button type="submit" class="btn btn-primary" aria-label="Send message">
        <?php icon('send'); ?>
      </button>
    </form>
  </div>
</div>