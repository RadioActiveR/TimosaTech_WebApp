/* Timosa Tech admin — keeps the Customer Support Chat tab live:
   polls the open thread for new messages AND refreshes the conversation
   sidebar (previews, statuses, ordering, unread dots) so an admin never
   has to reload the page to see incoming messages. */

document.addEventListener('DOMContentLoaded', () => {
  const container    = document.getElementById('supportChatMessages');
  const contactsList = document.querySelector('.support-contacts-list');

  // Nothing to poll if we're not on the support tab at all.
  if (!container && !contactsList) return;

  const POLL_MS = 4000;

  // NOTE: this path is relative to the PAGE (admin/admin-portal.php), not
  // to this JS file — so it must NOT start with '../'. The handler lives
  // at admin/includes/handlers/, i.e. inside the page's own folder.
  // ('../includes/handlers/...' resolves to the PUBLIC /includes/
  // directory at the project root, where this handler doesn't exist —
  // that 404 is why polling silently did nothing before.)
  const ENDPOINT = 'includes/handlers/admin-chat-handler.php';

  const selectedId = new URLSearchParams(window.location.search).get('conversation_id') || '';

  const conversationId = container ? container.dataset.conversationId : '';
  let lastId = container ? (parseInt(container.dataset.lastId, 10) || 0) : 0;

  // The open thread's header (status badge + "Return to Bot" button) is
  // server-rendered once on page load, same as the messages. Without this,
  // it goes stale the moment an admin's first reply flips the conversation
  // out of 'bot' status — the sidebar updates live, but the header doesn't,
  // until a full page reload.
  const statusBadge     = document.getElementById('supportChatStatusBadge');
  const returnToBotForm = document.getElementById('supportReturnToBotForm');

  // Tracks an in-flight reply send. Declared up here (not inside the reply
  // box section below) so poll() can see it too — see the guard at the top
  // of poll() for why this matters.
  let isSendingReply = false;

  /* ---------------- Open thread ---------------- */

  if (container) {
    // Show the most recent messages first, not the oldest ones — the
    // container renders top-to-bottom on load, so without this the
    // admin lands scrolled to the top of the conversation.
    container.scrollTop = container.scrollHeight;
  }

  // Sender labels shown above bot/visitor bubbles so they're easy to tell
  // apart at a glance — both currently sit on the left with similar
  // styling otherwise. Admin's own messages don't need one (they're the
  // only right-aligned, solid-cyan bubbles).
  const SUPPORT_MSG_LABELS = { bot: 'Stella (AI)', visitor: 'Customer' };

  // MySQL DATETIME string ("2026-09-16 10:23:00") -> "10:23 AM", matching
  // the format used by the PHP-rendered messages (date('g:i A', ...)).
  function formatMsgTime(dateStr) {
    const d = dateStr ? new Date(dateStr.replace(' ', 'T')) : new Date();
    return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }

  function renderMessage(msg) {
    // TEMP DIAGNOSTIC — remove once the duplicate is confirmed fixed.
    console.log('[chat-debug] renderMessage called', {
      id: msg.message_id,
      sender: msg.sender_type,
      text: msg.message,
      existingNode: !!container.querySelector('[data-message-id="' + msg.message_id + '"]'),
      time: performance.now().toFixed(1),
    });

    // Guards against duplicate renders when the manual post-send poll()
    // call and an already-in-flight scheduled poll both resolve with the
    // same message before lastId has caught up.
    if (container.querySelector('[data-message-id="' + msg.message_id + '"]')) {
      console.log('[chat-debug] duplicate blocked for id', msg.message_id);
      lastId = Math.max(lastId, Number(msg.message_id));
      return;
    }
    const el = document.createElement('div');
    el.className = 'support-msg support-msg-' + msg.sender_type;
    el.dataset.messageId = msg.message_id;

    const label = SUPPORT_MSG_LABELS[msg.sender_type];
    if (label) {
      const labelEl = document.createElement('span');
      labelEl.className = 'support-msg-label';
      labelEl.textContent = label;
      el.appendChild(labelEl);
    }

    const textEl = document.createElement('span');
    textEl.className = 'support-msg-text';
    textEl.textContent = msg.message;
    el.appendChild(textEl);

    const timeEl = document.createElement('span');
    timeEl.className = 'support-msg-time';
    timeEl.textContent = formatMsgTime(msg.created_at);
    el.appendChild(timeEl);

    container.appendChild(el);
    lastId = Math.max(lastId, Number(msg.message_id));
  }

  /* ---------------- Conversation sidebar ---------------- */

  // Tracks the newest activity timestamp we've *shown* per conversation,
  // so a thread that changes while the admin is looking elsewhere can be
  // flagged with an unread dot. Seeded on the first poll so existing
  // threads don't all light up the moment the page loads.
  const seenActivity = new Map();
  const unread = new Set();
  let seeded = false;

  // Rebuilding innerHTML on every poll would reset scroll position and
  // make the list flicker, so only redraw when something actually changed.
  let lastSignature = '';

  function buildContactRow(conv) {
    const row = document.createElement('a');
    row.href = '?tab=support&conversation_id=' + conv.conversation_id + '#adminContentArea';
    row.className = 'support-contact-row';
    if (String(conv.conversation_id) === String(selectedId)) {
      row.classList.add('active');
    }
    if (unread.has(conv.conversation_id)) {
      row.classList.add('has-new');
    }

    const top = document.createElement('div');
    top.className = 'support-contact-top';

    const name = document.createElement('span');
    name.className = 'support-contact-name';
    name.textContent = conv.display_name;

    const badge = document.createElement('span');
    badge.className = 'status-badge support-status-' + conv.status;
    badge.textContent = conv.status_label;

    top.appendChild(name);
    top.appendChild(badge);

    const preview = document.createElement('p');
    preview.className = 'support-contact-preview';
    preview.textContent = conv.preview || '';

    row.appendChild(top);
    row.appendChild(preview);
    return row;
  }

  // Keeps the open thread's header badge + Return-to-Bot button in sync
  // with live status, the same way renderContacts keeps the sidebar in
  // sync. Runs on every poll, not just after sending a reply, so a status
  // change made from another tab/admin also shows up without a reload.
  function syncHeaderStatus(conversations) {
    if (!conversationId || !statusBadge) return;

    const current = conversations.find(
      c => String(c.conversation_id) === String(conversationId)
    );
    if (!current) return;

    statusBadge.className = 'status-badge support-status-' + current.status;
    statusBadge.textContent = current.status_label;

    if (returnToBotForm) {
      returnToBotForm.style.display = current.status === 'bot' ? 'none' : '';
    }
  }

  function renderContacts(conversations) {
    // Track which threads have new activity since we last saw them.
    conversations.forEach(conv => {
      const prev = seenActivity.get(conv.conversation_id);
      if (seeded && prev !== undefined && prev !== conv.last_message_at
          && String(conv.conversation_id) !== String(selectedId)) {
        unread.add(conv.conversation_id);
      }
      seenActivity.set(conv.conversation_id, conv.last_message_at);
    });
    seeded = true;

    // The currently open thread is by definition being read right now.
    if (selectedId) unread.delete(Number(selectedId));

    const signature = conversations
      .map(c => [c.conversation_id, c.status, c.preview, c.last_message_at,
                 unread.has(c.conversation_id) ? 1 : 0].join('|'))
      .join('~');
    if (signature === lastSignature) return;
    lastSignature = signature;

    const scrollTop = contactsList.scrollTop;
    contactsList.innerHTML = '';

    if (!conversations.length) {
      const empty = document.createElement('p');
      empty.className = 'admin-panel-placeholder';
      empty.textContent = 'No conversations yet.';
      contactsList.appendChild(empty);
    } else {
      conversations.forEach(conv => contactsList.appendChild(buildContactRow(conv)));
    }

    contactsList.scrollTop = scrollTop;
  }

  /* ---------------- Poll ---------------- */

  // includeMessages=false is used right after an admin sends a reply: that
  // message is already rendered optimistically, so there's nothing new to
  // fetch for the thread — only the sidebar/header need a refresh. Skipping
  // the conversation_id/after_id params (instead of just discarding the
  // response) means the just-sent message never gets requested a second
  // time, so it can't rely on the render guard below to avoid a duplicate.
  async function poll(includeMessages = true) {
    // TEMP DIAGNOSTIC — remove once the duplicate is confirmed fixed.
    console.log('[chat-debug] poll() called', {
      includeMessages,
      isSendingReply,
      lastId,
      time: performance.now().toFixed(1),
    });

    // The recurring interval calls poll() with includeMessages=true on a
    // fixed 4s clock, completely independent of the reply form. If one of
    // those ticks is in flight (or fires) while a send is happening, it
    // captured `after_id` from *before* the send — so its response can
    // include the message we're about to render ourselves, arriving at an
    // unpredictable moment relative to our own optimistic render. The
    // render-guard below cleans that up, but the more reliable fix is to
    // just not let the interval ask for messages during that window at
    // all. The explicit poll(false) call after a send still goes through,
    // since includeMessages is false there.
    if (includeMessages && isSendingReply) {
      console.log('[chat-debug] poll() skipped — send in flight');
      return;
    }

    try {
      const params = new URLSearchParams();
      if (conversationId && includeMessages) {
        params.set('conversation_id', conversationId);
        params.set('after_id', String(lastId));
      }
      const res  = await fetch(ENDPOINT + '?' + params.toString());
      const data = await res.json();
      if (!data.success) return;

      if (container && Array.isArray(data.messages) && data.messages.length) {
        console.log('[chat-debug] poll() got messages', data.messages.map(m => m.message_id));
        // Only auto-scroll if the admin was already at the bottom —
        // otherwise we'd yank them away from older messages they're reading.
        const atBottom =
          container.scrollHeight - container.scrollTop - container.clientHeight < 60;
        data.messages.forEach(renderMessage);
        if (atBottom) container.scrollTop = container.scrollHeight;
      }

      if (Array.isArray(data.conversations)) {
        if (contactsList) renderContacts(data.conversations);
        syncHeaderStatus(data.conversations);
      }
    } catch (err) {
      // Ignore transient poll failures — it'll just retry next interval.
    }
  }

  poll();
  setInterval(poll, POLL_MS);

  /* ---------------- Reply box ---------------- */

  // Enter sends the reply, Shift+Enter inserts a newline (default
  // textarea behavior, so we only need to intercept plain Enter).
  const replyForm  = document.getElementById('supportReplyForm');
  const replyInput = document.getElementById('supportReplyInput');

  if (replyInput && replyForm) {
    // Tracks an in-flight send so a second Enter press (key-repeat from
    // holding it down, or a fast double-tap) can't fire a second AJAX
    // send before the first has cleared the textarea. Disabling the
    // submit button alone doesn't cover this, since the Enter handler
    // calls requestSubmit() directly rather than clicking the button.
    // (isSendingReply itself lives up top so poll() can also see it.)

    // Send via AJAX so the page doesn't reload (and the admin doesn't
    // lose scroll position / get bounced back through a redirect). The
    // form's normal POST to admin-portal.php still works if JS is off.
    replyForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      console.log('[chat-debug] submit fired', { isSendingReply, time: performance.now().toFixed(1) });

      if (isSendingReply) {
        console.log('[chat-debug] submit blocked — already sending');
        return;
      }

      const text = replyInput.value.trim();
      if (text === '' || !conversationId) return;

      isSendingReply = true;
      const button = replyForm.querySelector('button[type="submit"]');
      if (button) button.disabled = true;

      try {
        const body = new URLSearchParams({
          conversation_id: conversationId,
          message: text
        });
        const res  = await fetch(ENDPOINT, { method: 'POST', body });
        const data = await res.json();

        console.log('[chat-debug] send response', data);

        if (data.success && data.message) {
          renderMessage(data.message);
          container.scrollTop = container.scrollHeight;
          replyInput.value = '';
          replyInput.style.height = 'auto';
          // Refresh the sidebar/header immediately so this thread jumps to
          // the top with its new preview and updated status, instead of
          // waiting for the next tick. `false` skips re-fetching messages —
          // the reply above is already rendered, so there's nothing new to
          // fetch, and asking again is exactly how the same message used
          // to get rendered a second time.
          poll(false);
        }
      } catch (err) {
        // Do NOT resubmit here. A failure to read/parse the response
        // (e.g. a stray PHP warning printed ahead of the JSON, corrupting
        // it) doesn't mean the POST never reached the server — the insert
        // may well have already happened. Blindly resubmitting a
        // non-idempotent "send message" action on a read failure is how
        // you get a second, completely real row in chat_messages — which
        // is almost certainly what's been causing the duplicate that
        // survives a refresh, not a client-side render race.
        const errEl = document.createElement('div');
        errEl.className = 'support-msg support-msg-bot';
        errEl.textContent = "Couldn't confirm that reply went through — check the conversation before retrying, to avoid sending it twice.";
        container.appendChild(errEl);
        container.scrollTop = container.scrollHeight;
      } finally {
        isSendingReply = false;
        if (button) button.disabled = false;
      }
    });

    replyInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (replyInput.value.trim() !== '') {
          console.log('[chat-debug] Enter keydown -> requestSubmit', { time: performance.now().toFixed(1) });
          replyForm.requestSubmit();
        }
      }
    });

    // Auto-expand: grow with content up to a max height, then scroll.
    const maxHeight = 160;
    function autoExpand() {
      replyInput.style.height = 'auto';
      replyInput.style.height = Math.min(replyInput.scrollHeight, maxHeight) + 'px';
    }
    replyInput.addEventListener('input', autoExpand);
    autoExpand();
  }
});