/**
 * Product Details Modal
 * Shared by shop.php and homepage.php (and any other page using
 * includes/product-modal.php).
 *
 * Requires a global `window.isLoggedIn` boolean to be set on the page
 * before this script runs, e.g.:
 *   <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
 */
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('productModal');
  const closeModal = document.getElementById('closeModal');
  const isLoggedIn = window.isLoggedIn === true;

  document.querySelectorAll('.view-details-btn').forEach(button => {
    button.addEventListener('click', (e) => {
      e.preventDefault();

      if (!isLoggedIn) {
        const authOverlay = document.getElementById('authOverlay');
        if (authOverlay) {
          authOverlay.classList.add('active');
          document.body.style.overflow = 'hidden';
        }
        return;
      }

      modal.dataset.currentProductId = button.dataset.id;

      const modalTitle    = document.getElementById('modalTitle');
      const modalPrice    = document.getElementById('modalPrice');
      const modalCategory = document.getElementById('modalCategory');
      const modalStock    = document.getElementById('modalStock');
      const modalDesc     = document.getElementById('modalDesc');
      const modalImg      = document.getElementById('modalImg');

      if (modalTitle)    modalTitle.textContent = button.dataset.name;
      if (modalPrice)    modalPrice.textContent = button.dataset.price;
      if (modalCategory) modalCategory.textContent = button.dataset.category;
      if (modalStock)    modalStock.textContent = button.dataset.stock;
      if (modalDesc)     modalDesc.textContent = button.dataset.desc;
      if (modalImg)      modalImg.src = button.dataset.img;

      modal.classList.add('active');
    });
  });

  if (closeModal) {
    closeModal.addEventListener('click', () => modal.classList.remove('active'));
  }

  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) modal.classList.remove('active');
    });
  }
});