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
  let sending        = false;
  // Tracks the conversation's last known status so we only show the
  // "typing" indicator when a bot reply is actually expected — once a
  // human has taken over, chat-handler.php stops generating bot replies
  // entirely, and the indicator would just flash and vanish.
  let knownStatus    = 'bot';

  // Sender labels shown above bot/admin bubbles so a visitor can tell
  // Stella (AI) apart from a human rep at a glance.
  const CHAT_MSG_LABELS = { bot: 'Stella (AI)', admin: 'Support Agent' };

  // MySQL DATETIME string ("2026-09-16 10:23:00") -> "10:23 AM". Falls
  // back to the current time for messages that don't have one yet (the
  // canned greeting, and the visitor's own optimistic bubble before the
  // server confirms it).
  function formatMsgTime(dateStr) {
    const d = dateStr ? new Date(dateStr.replace(' ', 'T')) : new Date();
    return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }

  function renderMessage(msg) {
    const el = document.createElement('div');
    el.className = 'chat-msg chat-msg-' + msg.sender_type;

    const label = CHAT_MSG_LABELS[msg.sender_type];
    if (label) {
      const labelEl = document.createElement('span');
      labelEl.className = 'chat-msg-label';
      labelEl.textContent = label;
      el.appendChild(labelEl);
    }

    const textEl = document.createElement('span');
    textEl.className = 'chat-msg-text';
    textEl.textContent = msg.message;
    el.appendChild(textEl);

    const timeEl = document.createElement('span');
    timeEl.className = 'chat-msg-time';
    timeEl.textContent = formatMsgTime(msg.created_at);
    el.appendChild(timeEl);

    messagesEl.appendChild(el);
    if (msg.message_id) {
      lastMessageId = Math.max(lastMessageId, Number(msg.message_id));
    }
  }

  function showTyping() {
    if (document.getElementById('chatWidgetTyping')) return;
    const el = document.createElement('div');
    el.id = 'chatWidgetTyping';
    el.className = 'chat-msg chat-msg-bot chat-typing';
    el.innerHTML =
      '<span class="chat-msg-label">Stella (AI)</span>' +
      '<span class="chat-typing-dots"><span></span><span></span><span></span></span>';
    messagesEl.appendChild(el);
    scrollToBottom();
  }

  function hideTyping() {
    const el = document.getElementById('chatWidgetTyping');
    if (el) el.remove();
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
      knownStatus = data.status;
      messagesEl.innerHTML = '';

      if (data.messages.length === 0) {
        renderMessage({
          sender_type: 'bot',
          message: "Hi! I'm the TimosaTech assistant. Ask me about products, services, or say \"talk to a human\" any time.",
        });
      } else {
        data.messages.forEach(renderMessage);
      }

      scrollToBottom();
      // No conversation exists yet for a brand-new visitor — nothing to
      // poll until they actually send a message (see the submit handler
      // below, which starts polling once that first send creates one).
      if (conversationId) startPolling();
    } catch (err) {
      // Silent fail — widget stays empty until the visitor tries again.
    }
  }

  function startPolling() {
    if (pollTimer) return;
    pollTimer = setInterval(async () => {
      if (!conversationId || sending) return;
      try {
        const res = await fetch(
          `../includes/handlers/chat-handler.php?action=poll&conversation_id=${conversationId}&after_id=${lastMessageId}`
        );
        const data = await res.json();
        if (data.success) {
          knownStatus = data.status;
          if (data.messages.length) {
            data.messages.forEach(renderMessage);
            scrollToBottom();
          }
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

  // Enter sends the message, Shift+Enter inserts a newline (default
  // textarea behavior, so we only need to intercept plain Enter).
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (input.value.trim() !== '') {
        form.requestSubmit();
      }
    }
  });

  // Auto-expand: grow with content up to a max height, then scroll.
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

    // Only show "Stella is typing" when a bot reply is actually coming —
    // once a human has taken over, chat-handler.php stops generating bot
    // replies, so the indicator would just flash and disappear for no
    // reason if shown unconditionally.
    if (knownStatus === 'bot') showTyping();

    // Pausing polling for the duration of this request closes the race
    // where a scheduled poll fetches this same visitor message from the
    // DB (chat-handler.php saves it immediately, before the bot reply is
    // generated) while it's already showing here as an optimistic render
    // — which is what caused the visible-until-refresh duplicate.
    sending = true;
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
        knownStatus = data.status;
        // Redraw from the authoritative server list so we don't end up
        // with a duplicate of the message we already drew optimistically.
        // This also clears the typing indicator, since it's not part of
        // data.messages.
        messagesEl.innerHTML = '';
        lastMessageId = 0;
        data.messages.forEach(renderMessage);
        scrollToBottom();
        startPolling();
      } else {
        hideTyping();
      }
    } catch (err) {
      hideTyping();
      const errEl = document.createElement('div');
      errEl.className = 'chat-msg chat-msg-system';
      errEl.textContent = "Couldn't send that — check your connection and try again.";
      messagesEl.appendChild(errEl);
    } finally {
      sending = false;
    }
  });
});