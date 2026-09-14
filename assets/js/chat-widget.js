/* Timosa Tech — floating support chat widget (open/close, send, poll). */

document.addEventListener('DOMContentLoaded', () => {
  const widget       = document.getElementById('chatWidget');
  const toggleBtn     = document.getElementById('chatWidgetToggle');
  const closeBtn      = document.getElementById('chatWidgetClose');
  const messagesEl    = document.getElementById('chatWidgetMessages');
  const form          = document.getElementById('chatWidgetForm');
  const input         = document.getElementById('chatWidgetInput');

  if (!widget) return;

  let conversationId = null;
  let lastMessageId  = 0;
  let pollTimer      = null;
  let historyLoaded  = false;

  function renderMessage(msg) {
    const el = document.createElement('div');
    el.className = 'chat-msg chat-msg-' + msg.sender_type;
    el.textContent = msg.message;
    messagesEl.appendChild(el);
    if (msg.message_id) {
      lastMessageId = Math.max(lastMessageId, Number(msg.message_id));
    }
  }

  function scrollToBottom() {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  async function loadHistory() {
    if (historyLoaded) return;
    historyLoaded = true;

    try {
      const res = await fetch('../includes/handlers/chat-handler.php?action=history');
      const data = await res.json();
      if (!data.success) return;

      conversationId = data.conversation_id;
      messagesEl.innerHTML = '';

      if (data.messages.length === 0) {
        const el = document.createElement('div');
        el.className = 'chat-msg chat-msg-bot';
        el.textContent = "Hi! I'm the TimosaTech assistant. Ask me about products, services, or say \"talk to a human\" any time.";
        messagesEl.appendChild(el);
      } else {
        data.messages.forEach(renderMessage);
      }

      scrollToBottom();
      startPolling();
    } catch (err) {
      // Silent fail — widget stays empty until the visitor tries again.
    }
  }

  function startPolling() {
    if (pollTimer) return;
    pollTimer = setInterval(async () => {
      if (!conversationId) return;
      try {
        const res = await fetch(
          `../includes/handlers/chat-handler.php?action=poll&conversation_id=${conversationId}&after_id=${lastMessageId}`
        );
        const data = await res.json();
        if (data.success && data.messages.length) {
          data.messages.forEach(renderMessage);
          scrollToBottom();
        }
      } catch (err) {
        // Ignore transient poll failures — it'll just retry next interval.
      }
    }, 4000);
  }

  toggleBtn.addEventListener('click', () => {
    widget.classList.toggle('active');
    if (widget.classList.contains('active')) {
      loadHistory();
      input.focus();
    }
  });

  closeBtn.addEventListener('click', () => widget.classList.remove('active'));

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;

    input.value = '';
    renderMessage({ sender_type: 'visitor', message: text });
    scrollToBottom();

    try {
      const body = new URLSearchParams({ action: 'send', message: text });
      const res = await fetch('../includes/handlers/chat-handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body,
      });
      const data = await res.json();

      if (data.success) {
        conversationId = data.conversation_id;
        // Redraw from the authoritative server list so we don't end up
        // with a duplicate of the message we already drew optimistically.
        messagesEl.innerHTML = '';
        data.messages.forEach(renderMessage);
        scrollToBottom();
        startPolling();
      }
    } catch (err) {
      const errEl = document.createElement('div');
      errEl.className = 'chat-msg chat-msg-system';
      errEl.textContent = "Couldn't send that — check your connection and try again.";
      messagesEl.appendChild(errEl);
    }
  });
});