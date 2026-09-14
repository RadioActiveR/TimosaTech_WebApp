<?php
$modal_hidden = isset($pdo) && function_exists('is_modal_hidden') && is_modal_hidden($pdo, 'modal_product');
?>
<!-- PRODUCT DETAILS MODAL -->
  <div class="modal-overlay" id="productModal">
    <div class="product-modal<?= $modal_hidden ? ' product-modal-veiled' : '' ?>">
      <button class="modal-close" id="closeModal">&times;</button>
      <?php if ($modal_hidden): ?>
      <div class="center-container">
        <h2 class="hidden"> HIDDEN </h2>
        <h3 class="hidden-subtext"> Protocol 'CONTENT VEIL' active. Public routing disabled by Administrator. </h3>
      </div>
      <?php else: ?>
      <div class="modal-image-container" id="modalImageContainer">
        <div class="modal-image-track" id="modalImageTrack"></div>
        <button type="button" class="modal-img-nav modal-img-prev" id="modalImgPrev" aria-label="Previous image">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 18l-6-6 6-6"></path>
          </svg>
        </button>
        <button type="button" class="modal-img-nav modal-img-next" id="modalImgNext" aria-label="Next image">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 18l6-6-6-6"></path>
          </svg>
        </button>
        <div class="modal-image-dots" id="modalImageDots"></div>
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