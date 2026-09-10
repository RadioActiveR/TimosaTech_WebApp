/* Timosa Tech — Cart modal (open/close, add/update/remove via AJAX,
   select-before-checkout) + quick add-to-cart buttons on product cards */

/* INFO: Linked Files:

    includes/cart-handler

*/

document.addEventListener("DOMContentLoaded", function () {
  const cartOverlay     = document.getElementById("cartOverlay");
  const cartClose       = document.getElementById("cartClose");
  const openCartBtns    = document.querySelectorAll("[data-open-cart]");
  const cartItemsList   = document.getElementById("cartItemsList");
  const cartTotalValue  = document.getElementById("cartTotalValue");
  const cartOrderBtn    = document.getElementById("cartOrderBtn");
  const cartSelectAll   = document.getElementById("cartSelectAll");
  const cartCountBadges = document.querySelectorAll(".cart-count-badge");
  const authOverlay     = document.getElementById("authOverlay");
  const productModal    = document.getElementById("productModal");
  const addToCartBtn    = document.getElementById("modalAddToCart");

  if (!cartOverlay) return;

  // Tracks which cart_item_ids the user has *unchecked*. Items not in this
  // set are considered selected. Kept in JS memory (not storage) so it
  // survives the full-HTML re-renders that happen after every add/update/
  // remove AJAX call, without needing a round trip to the server just to
  // toggle a checkbox. Newly added items default to selected (not in the set).
  let uncheckedItems = new Set();
  let lastCartData   = null;

  function openCart() {
    cartOverlay.classList.add("active");
    document.body.style.overflow = "hidden";
  }

  function closeCart() {
    cartOverlay.classList.remove("active");
    document.body.style.overflow = "";
  }

  function escapeHtml(str) {
    const div = document.createElement("div");
    div.textContent = str ?? "";
    return div.innerHTML;
  }

  // Small floating confirmation/error message, used by the quick add-to-cart
  // buttons on the product cards so we don't have to pop the cart modal open
  // just to add one item.
  function showToast(message, isError = false) {
    let toast = document.getElementById("cartToast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "cartToast";
      toast.className = "cart-toast";
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.toggle("error", isError);
    toast.classList.add("show");
    clearTimeout(toast._hideTimer);
    toast._hideTimer = setTimeout(() => toast.classList.remove("show"), 2200);
  }

  // Recomputes the "selected total" and the Order Now button's disabled
  // state from the current cart data + uncheckedItems set, without touching
  // the DOM list itself. Called on every checkbox toggle.
  function updateSelectionUI(data) {
    if (!data) return;

    let selectedTotal = 0;
    let selectedCount = 0;
    data.items.forEach(item => {
      if (!uncheckedItems.has(String(item.cart_item_id))) {
        selectedTotal += Number(item.subtotal);
        selectedCount++;
      }
    });

    if (cartTotalValue) {
      cartTotalValue.textContent = "$" + selectedTotal.toFixed(2);
    }

    if (cartOrderBtn) {
      cartOrderBtn.disabled = selectedCount === 0;
    }

    if (cartSelectAll) {
      cartSelectAll.checked = data.items.length > 0 && selectedCount === data.items.length;
      cartSelectAll.indeterminate = selectedCount > 0 && selectedCount < data.items.length;
    }
  }

  function renderCart(data) {
    lastCartData = data;

    cartCountBadges.forEach(badge => {
      badge.textContent = data.count;
      badge.style.display = data.count > 0 ? "inline-flex" : "none";
    });

    if (cartSelectAll) {
      cartSelectAll.closest(".cart-select-all-row")?.style.setProperty(
        "display", data.items.length > 0 ? "" : "none"
      );
    }

    if (cartItemsList) {
      if (!data.items.length) {
        cartItemsList.innerHTML = '<p class="cart-empty-msg">Your cart is empty.</p>';
      } else {
        cartItemsList.innerHTML = data.items.map(item => {
          const isChecked = !uncheckedItems.has(String(item.cart_item_id));
          return `
            <div class="cart-item" data-cart-item-id="${item.cart_item_id}">
              <label class="cart-item-select">
                <input type="checkbox"
                       name="selected_items[]"
                       value="${item.cart_item_id}"
                       class="cart-item-checkbox"
                       aria-label="Select ${escapeHtml(item.name)} for checkout"
                       ${isChecked ? "checked" : ""}>
              </label>
              <img src="${item.image_src}" alt="${escapeHtml(item.name)}">
              <div class="cart-item-info">
                <h4>${escapeHtml(item.name)}</h4>
                <span class="cart-item-price">$${Number(item.price).toFixed(2)}</span>
                <div class="cart-qty-controls">
                  <button type="button" class="cart-qty-btn" data-delta="-1" aria-label="Decrease quantity">&minus;</button>
                  <span class="cart-qty-value">${item.quantity}</span>
                  <button type="button" class="cart-qty-btn" data-delta="1" aria-label="Increase quantity">&plus;</button>
                  <button type="button" class="cart-remove-btn">Remove</button>
                </div>
              </div>
              <div class="cart-item-subtotal">$${Number(item.subtotal).toFixed(2)}</div>
            </div>
          `;
        }).join("");
      }
    }

    updateSelectionUI(data);
  }

  async function cartRequest(action, params = {}) {
    const body = new URLSearchParams({ action, ...params });
    let res;
    try {
      res = await fetch("../includes/cart-handler.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body
      });
    } catch (err) {
      return null;
    }

    if (res.status === 401) {
      closeCart();
      if (authOverlay) {
        authOverlay.classList.add("active");
        document.body.style.overflow = "hidden";
      }
      return null;
    }

    const data = await res.json();
    renderCart(data);
    return data;
  }

  openCartBtns.forEach(btn => {
    btn.addEventListener("click", e => {
      e.preventDefault();
      openCart();
    });
  });

  if (cartClose) cartClose.addEventListener("click", closeCart);

  cartOverlay.addEventListener("click", e => {
    if (e.target === cartOverlay) closeCart();
  });

  document.addEventListener("keydown", e => {
    if (e.key === "Escape" && cartOverlay.classList.contains("active")) closeCart();
  });

  // Delegate qty +/-, remove, and checkbox clicks since the list is fully
  // re-rendered on every add/update/remove.
  if (cartItemsList) {
    cartItemsList.addEventListener("click", e => {
      const itemEl = e.target.closest(".cart-item");
      if (!itemEl) return;
      const cartItemId = itemEl.dataset.cartItemId;

      if (e.target.classList.contains("cart-qty-btn")) {
        const delta = parseInt(e.target.dataset.delta, 10);
        const qtyEl = itemEl.querySelector(".cart-qty-value");
        const newQty = parseInt(qtyEl.textContent, 10) + delta;
        cartRequest("update", { cart_item_id: cartItemId, quantity: newQty });
      }

      if (e.target.classList.contains("cart-remove-btn")) {
        uncheckedItems.delete(String(cartItemId));
        cartRequest("remove", { cart_item_id: cartItemId });
      }
    });

    cartItemsList.addEventListener("change", e => {
      if (!e.target.classList.contains("cart-item-checkbox")) return;
      const itemEl = e.target.closest(".cart-item");
      const cartItemId = String(itemEl.dataset.cartItemId);

      if (e.target.checked) {
        uncheckedItems.delete(cartItemId);
      } else {
        uncheckedItems.add(cartItemId);
      }
      updateSelectionUI(lastCartData);
    });
  }

  // "Select all" toggle — pure client-side, no server round trip needed.
  if (cartSelectAll) {
    cartSelectAll.addEventListener("change", () => {
      if (!lastCartData) return;

      if (cartSelectAll.checked) {
        uncheckedItems.clear();
      } else {
        lastCartData.items.forEach(item => uncheckedItems.add(String(item.cart_item_id)));
      }

      cartItemsList.querySelectorAll(".cart-item-checkbox").forEach(cb => {
        cb.checked = cartSelectAll.checked;
      });
      updateSelectionUI(lastCartData);
    });
  }

  // "Add to Cart" button inside the product details modal
  if (addToCartBtn && productModal) {
    addToCartBtn.addEventListener("click", async () => {
      const productId = productModal.dataset.currentProductId;
      if (!productId) return;

      const data = await cartRequest("add", { product_id: productId, quantity: 1 });
      if (data && data.success) {
        productModal.classList.remove("active");
        openCart();
      }
    });
  }

  // Quick add-to-cart buttons on the product cards (homepage + shop grid).
  // Delegated on the document since these buttons are rendered server-side
  // per page and never re-rendered like the cart item list is.
  document.addEventListener("click", async (e) => {
    const quickAddBtn = e.target.closest(".quick-add-btn");
    if (!quickAddBtn) return;
    e.preventDefault();

    if (quickAddBtn.disabled) return;
    const productId = quickAddBtn.dataset.id;
    if (!productId) return;

    quickAddBtn.disabled = true;
    const data = await cartRequest("add", { product_id: productId, quantity: 1 });
    quickAddBtn.disabled = false;

    if (data === null) return; // not logged in (auth modal already opened) or network error

    if (data.success) {
      showToast("Added to cart");
    } else {
      showToast(data.error || "Couldn't add that item to your cart.", true);
    }
  });
});