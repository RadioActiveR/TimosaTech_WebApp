/* Timosa Tech — watches for admin Site Controls changes (page/modal/
   widget visibility toggles) and reloads the current page automatically
   when one happens, so a visitor doesn't keep seeing stale content (or a
   stale Content Veil) until they manually refresh.

   Included on every public (non-admin) page via components/header.php. */

(function () {
  const ENDPOINT = '../includes/handlers/site-controls-status-handler.php';
  const POLL_MS = 15000;

  // null until the first successful check — that first check only
  // establishes the baseline for this page load, it never triggers a
  // reload on its own (the version obviously "differs" from nothing).
  let knownVersion = null;
  let pollTimer = null;

  async function checkVersion() {
    try {
      const res = await fetch(ENDPOINT, { cache: 'no-store' });
      const data = await res.json();
      if (!data || !data.version) return;

      if (knownVersion === null) {
        knownVersion = data.version;
        return;
      }

      if (data.version !== knownVersion) {
        location.reload();
      }
    } catch (err) {
      // Ignore transient failures — it'll just retry next interval.
    }
  }

  function startPolling() {
    if (pollTimer) return;
    pollTimer = setInterval(checkVersion, POLL_MS);
  }

  function stopPolling() {
    clearInterval(pollTimer);
    pollTimer = null;
  }

  // Pause while the tab is backgrounded — no point spending requests on a
  // tab nobody's looking at — and check immediately the moment it
  // regains focus, so a change made while the visitor was away is caught
  // right away instead of waiting for the next scheduled tick.
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopPolling();
    } else {
      checkVersion();
      startPolling();
    }
  });

  checkVersion();
  startPolling();
})();