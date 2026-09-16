/* Timosa Tech — notification bell (header) + chat widget unread badge.
 * Runs on every page via header.php. window.notifRole / window.notifEndpoint
 * are set inline by header.php based on whether we're on the admin portal
 * or a regular page — see there for why the endpoint path is absolute. */

document.addEventListener('DOMContentLoaded', () => {
  const role = window.notifRole;
  const endpoint = window.notifEndpoint;
  if (!role || !endpoint) return;

  const bellBtn    = document.getElementById('notifBellBtn');
  const panel      = document.getElementById('notifPanel');
  const listEl     = document.getElementById('notifList');
  const countBadge = document.getElementById('notifCountBadge');
  const markAllBtn = document.getElementById('notifMarkAllBtn');
  const widgetToggle = document.getElementById('chatWidgetToggle'); // absent on admin page

  if (!bellBtn || !panel || !listEl) return;

  const POLL_MS = 5000;
  let isOpen = false;
  let notifications = [];

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
  }

  // created_at comes back as a MySQL DATETIME string ("2026-09-16 10:23:00").
  function timeAgo(dateStr) {
    const then = new Date(dateStr.replace(' ', 'T'));
    const diffMs = Date.now() - then.getTime();
    const mins = Math.floor(diffMs / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return mins + 'm ago';
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return hrs + 'h ago';
    return Math.floor(hrs / 24) + 'd ago';
  }

  function updateCountBadge(count) {
    if (!countBadge) return;
    countBadge.textContent = count > 99 ? '99+' : String(count);
    countBadge.style.display = count > 0 ? '' : 'none';
  }

  // The floating widget's badge doesn't exist in the page's initial HTML
  // (the widget markup is shared across every page and predates this
  // feature) — create it lazily the first time it's needed, same pattern
  // cart.js already uses for its toast element.
  function updateChatWidgetBadge(count) {
    if (!widgetToggle) return;
    let badge = document.getElementById('chatWidgetBadge');
    if (!badge) {
      badge = document.createElement('span');
      badge.id = 'chatWidgetBadge';
      badge.className = 'chat-widget-badge';
      widgetToggle.appendChild(badge);
    }
    badge.textContent = count > 9 ? '9+' : String(count);
    badge.style.display = count > 0 ? '' : 'none';
  }

  function renderList() {
    if (!notifications.length) {
      listEl.innerHTML = '<p class="notif-empty">You\'re all caught up.</p>';
      return;
    }
    listEl.innerHTML = notifications.map(n => `
      <a href="${escapeHtml(n.link || '#')}" class="notif-item ${Number(n.is_read) === 0 ? 'unread' : ''}" data-id="${n.notification_id}">
        <div class="notif-item-top">
          <span class="notif-item-title">${escapeHtml(n.title)}</span>
          <span class="notif-item-time">${timeAgo(n.created_at)}</span>
        </div>
        ${n.message ? `<p class="notif-item-msg">${escapeHtml(n.message)}</p>` : ''}
      </a>
    `).join('');
  }

  async function fetchNotifications() {
    try {
      const res = await fetch(endpoint + '?action=list');
      const data = await res.json();
      if (!data.success) return;

      notifications = data.notifications || [];
      updateCountBadge(data.unread_count || 0);
      if (role === 'user') updateChatWidgetBadge(data.unread_chat || 0);
      if (isOpen) renderList();
    } catch (err) {
      // Ignore transient poll failures — retried on the next interval.
    }
  }

  async function postAction(action, extra = {}) {
    try {
      await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action, ...extra }),
      });
    } catch (err) {
      // Non-critical — the next poll resyncs the real state regardless.
    }
  }

  function markAllRead() {
    notifications.forEach(n => { n.is_read = 1; });
    updateCountBadge(0);
    if (role === 'user') updateChatWidgetBadge(0);
    renderList();
    postAction('mark_all_read');
  }

  bellBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    isOpen = !isOpen;
    panel.classList.toggle('active', isOpen);
    bellBtn.setAttribute('aria-expanded', String(isOpen));
    if (isOpen) renderList();
  });

  document.addEventListener('click', (e) => {
    if (isOpen && !panel.contains(e.target) && e.target !== bellBtn) {
      isOpen = false;
      panel.classList.remove('active');
      bellBtn.setAttribute('aria-expanded', 'false');
    }
  });

  if (markAllBtn) {
    markAllBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      markAllRead();
    });
  }

  listEl.addEventListener('click', (e) => {
    const item = e.target.closest('.notif-item');
    if (!item) return;
    postAction('mark_read', { notification_id: item.dataset.id });
    // Navigation proceeds via the anchor's own href — no preventDefault.
  });

  // Opening the floating chat widget counts as reading any pending chat
  // reply notifications specifically, without touching unrelated
  // order-update notifications sitting in the bell dropdown.
  if (widgetToggle && role === 'user') {
    widgetToggle.addEventListener('click', () => {
      updateChatWidgetBadge(0);
      postAction('mark_read_chat');
    });
  }

  fetchNotifications();
  setInterval(fetchNotifications, POLL_MS);
});