/* Timosa Tech — Copy-to-clipboard for order reference numbers.
 * Shared by pages/profile.php (order history) and
 * pages/order-confirmation.php (the "Thank you" banner). Delegated on
 * document so it works regardless of how/where the button is rendered.
 *
 * Usage: <button type="button" class="copy-order-id-btn" data-copy="ord_123">...</button>
 */
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".copy-order-id-btn");
  if (!btn) return;

  const text = btn.dataset.copy;
  if (!text) return;

  const markCopied = () => {
    btn.classList.add("copied");
    btn.setAttribute("aria-label", "Copied");
    clearTimeout(btn._copiedTimer);
    btn._copiedTimer = setTimeout(() => {
      btn.classList.remove("copied");
      btn.setAttribute("aria-label", "Copy order number");
    }, 1500);
  };

  const fallbackCopy = () => {
    const ta = document.createElement("textarea");
    ta.value = text;
    ta.style.position = "fixed";
    ta.style.opacity = "0";
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand("copy");
      markCopied();
    } catch (err) {
      // Clipboard truly unavailable — silently no-op rather than error.
    }
    document.body.removeChild(ta);
  };

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(markCopied).catch(fallbackCopy);
  } else {
    fallbackCopy();
  }
});