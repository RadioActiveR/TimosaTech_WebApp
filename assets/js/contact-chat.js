/* Timosa Tech — larger inline chat panel on the Contact page.
   Same backend as the floating widget (chat-handler.php), just always
   visible instead of toggled, so there's no open/close logic here. */

document.addEventListener('DOMContentLoaded', () => {
  const messagesEl = document.getElementById('contactChatMessages');
  const form        = document.getElementById('contactChatForm');
  const input        = document.getElementById('contactChatInput');

  if (!messagesEl || !form || !input) return;

  let conversationId = null;
  let lastMessageId  = 0;

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
      // Silent fail — panel stays empty until the visitor tries again.
    }
  }

  function startPolling() {
    setInterval(async () => {
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
        // Ignore transient poll failures.
      }
    }, 4000);
  }

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (input.value.trim() !== '') {
        form.requestSubmit();
      }
    }
  });

  const maxInputHeight = 120;
  function autoExpandInput() {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, maxInputHeight) + 'px';
  }
  input.addEventListener('input', autoExpandInput);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;

    input.value = '';
    autoExpandInput();
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
        messagesEl.innerHTML = '';
        data.messages.forEach(renderMessage);
        scrollToBottom();
      }
    } catch (err) {
      const errEl = document.createElement('div');
      errEl.className = 'chat-msg chat-msg-system';
      errEl.textContent = "Couldn't send that — check your connection and try again.";
      messagesEl.appendChild(errEl);
    }
  });

  loadHistory();
});