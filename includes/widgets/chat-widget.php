<?php
/**
 * Floating site-wide support chat widget. Local Ollama bot answers by
 * default; escalates to a human (picked up via the admin dashboard's
 * Customer Support Chat tab) the moment the visitor asks for one.
 * Available to guests and logged-in users alike — no login required.
 * Included from components/header.php on every non-admin page.
 */
$widget_hidden = isset($pdo) && function_exists('is_widget_hidden') && is_widget_hidden($pdo, 'widget_chat');
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

    <?php if ($widget_hidden): ?>
      <div class="center-container">
        <h2 class="hidden"> HIDDEN </h2>
        <h3 class="hidden-subtext"> Protocol 'CONTENT VEIL' active. Public routing disabled by Administrator. </h3>
      </div>
    <?php else: ?>
    <div class="chat-widget-messages" id="chatWidgetMessages"></div>

    <form class="chat-widget-form" id="chatWidgetForm">
      <textarea id="chatWidgetInput" rows="1" placeholder="Type a message... (Enter to send, Shift+Enter for a new line)" autocomplete="off"></textarea>
      <button type="submit" class="btn btn-primary" aria-label="Send message">
        <?php icon('send'); ?>
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>