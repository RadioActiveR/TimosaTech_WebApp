/* Timosa Tech admin — polls the open support chat thread for new
   messages so an admin sees incoming replies without refreshing. */

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('supportChatMessages');
  if (!container) return;

  const conversationId = container.dataset.conversationId;
  let lastId = parseInt(container.dataset.lastId, 10) || 0;

  function renderMessage(msg) {
    const el = document.createElement('div');
    el.className = 'support-msg support-msg-' + msg.sender_type;
    el.dataset.messageId = msg.message_id;
    el.textContent = msg.message;
    container.appendChild(el);
    lastId = Math.max(lastId, Number(msg.message_id));
  }

  setInterval(async () => {
    try {
      const res = await fetch(
        `../includes/handlers/admin-chat-handler.php?conversation_id=${conversationId}&after_id=${lastId}`
      );
      const data = await res.json();
      if (data.success && data.messages.length) {
        data.messages.forEach(renderMessage);
        container.scrollTop = container.scrollHeight;
      }
    } catch (err) {
      // Ignore transient poll failures — it'll just retry next interval.
    }
  }, 4000);
});