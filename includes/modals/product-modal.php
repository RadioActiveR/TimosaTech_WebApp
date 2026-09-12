<?php
$modal_hidden = isset($pdo) && function_exists('is_modal_hidden') && is_modal_hidden($pdo, 'modal_product');
?>
<!-- PRODUCT DETAILS MODAL -->
  <div class="modal-overlay" id="productModal">
    <div class="product-modal<?= $modal_hidden ? ' product-modal-veiled' : '' ?>">
      <button class="modal-close" id="closeModal">&times;</button>
      <?php if ($modal_hidden): ?>
      <h2 class="hidden">HIDDEN</h2>
      <?php else: ?>
      <div class="modal-image-container">
        <img id="modalImg" src="" alt="Product Image">
      </div>
      <div class="modal-details">
        <h2 id="modalTitle">Product Title</h2>
        <div class="modal-meta">
          <span>Category: <strong id="modalCategory" style="color:#fff;">-</strong></span>
          <span>In Stock: <strong id="modalStock" style="color:#fff;">-</strong></span>
        </div>
        <p class="modal-desc" id="modalDesc"></p>
        <div class="modal-footer">
          <span class="modal-price" id="modalPrice">₱0.00</span>
          <button class="btn btn-primary" id="modalAddToCart" type="button">Add to Cart</button>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>