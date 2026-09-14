<?php
$modal_hidden = isset($pdo) && function_exists('is_modal_hidden') && is_modal_hidden($pdo, 'modal_cart');
/* INFO: Shopping cart modal. Include this right after auth-modal.php on any
 * page that shows the cart button (shop.php, homepage.php). Renders the
 * user's current cart server-side for first paint / no-JS fallback; js/cart.js
 * takes over from there and re-renders after every add/update/remove.
 * Requires: session_start() + db.php already loaded by the parent page.
 *
 * INFO (cart select-before-checkout): each item has a checkbox
 * (name="selected_items[]") so the user can choose which items to buy right
 * now while leaving the rest in the cart. The whole item list + "Order Now"
 * button live inside one <form> that POSTs straight to checkout.php —
 * unchecked items are simply never submitted, no JS required for the
 * submission itself (JS only handles live totals / select-all / enabling
 * the button).
 */

/* INFO: Linked Files:

    cart-functions.php

*/

require_once __DIR__ . '/../functions/cart-functions.php';

$cart_items = [];
$cart_total = 0;
if (!$modal_hidden && isset($_SESSION['u_id'])) {
    $cart_items = get_cart_items($pdo, $_SESSION['u_id']);
    foreach ($cart_items as $ci) {
        $cart_total += $ci['price'] * $ci['quantity'];
    }
}
?>
<div class="modal-overlay" id="cartOverlay">
  <div class="cart-modal">
    <div class="cart-modal-header">
      <h2>Your Cart</h2>
      <button type="button" class="modal-close" id="cartClose" aria-label="Close">&times;</button>
    </div>

    <?php if ($modal_hidden): ?>
      <div class="center-container">
        <h2 class="hidden"> HIDDEN </h2>
        <h3 class="hidden-subtext"> Protocol 'CONTENT VEIL' active. Public routing disabled by Administrator. </h3>
      </div>
    <?php else: ?>

    <form method="post" action="checkout.php" id="cartCheckoutForm">

      <?php if (!empty($cart_items)): ?>
        <div class="cart-select-all-row">
          <label class="cart-select-all-label">
            <input type="checkbox" id="cartSelectAll" checked>
            Select all
          </label>
        </div>
      <?php endif; ?>

      <div class="cart-items-list" id="cartItemsList">
        <?php if (empty($cart_items)): ?>
          <p class="cart-empty-msg">Your cart is empty.</p>
        <?php else: ?>
          <?php foreach ($cart_items as $item):
            $img_src = $item['image_url'];
          ?>
            <div class="cart-item" data-cart-item-id="<?= $item['cart_item_id'] ?>">
              <label class="cart-item-select">
                <input type="checkbox"
                       name="selected_items[]"
                       value="<?= $item['cart_item_id'] ?>"
                       class="cart-item-checkbox"
                       aria-label="Select <?= htmlspecialchars($item['name']) ?> for checkout"
                       checked>
              </label>
              <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
              <div class="cart-item-info">
                <h4><?= htmlspecialchars($item['name']) ?></h4>
                <span class="cart-item-price">₱<?= number_format($item['price'], 2) ?></span>
                <div class="cart-qty-controls">
                  <button type="button" class="cart-qty-btn" data-delta="-1" aria-label="Decrease quantity">&minus;</button>
                  <span class="cart-qty-value"><?= intval($item['quantity']) ?></span>
                  <button type="button" class="cart-qty-btn" data-delta="1" aria-label="Increase quantity">&plus;</button>
                  <button type="button" class="cart-remove-btn">Remove</button>
                </div>
              </div>
              <div class="cart-item-subtotal">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="cart-modal-footer">
        <div class="cart-total-row">
          <span>Total (selected)</span>
          <strong id="cartTotalValue">₱<?= number_format($cart_total, 2) ?></strong>
        </div>
        <button type="submit" class="btn btn-primary cart-order-btn" id="cartOrderBtn"
           <?= empty($cart_items) ? 'disabled' : '' ?>>
          Order Now
        </button>
      </div>
    </form>

    <?php endif; ?>

  </div>
</div>