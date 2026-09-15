/**
 * Product Details Modal
 * Shared by shop.php and homepage.php (and any other page using
 * includes/product-modal.php).
 *
 * Requires a global `window.isLoggedIn` boolean to be set on the page
 * before this script runs, e.g.:
 *   <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
 *
 * Image gallery:
 * Each "View Details" button can carry a `data-images` attribute — a JSON
 * array of image URLs, e.g. data-images='["a.jpg","b.jpg"]'. If present,
 * the modal shows a swipeable gallery with prev/next corner buttons and
 * dot indicators (arrows/dots auto-hide when there's only one image).
 * If `data-images` is missing or invalid, this falls back to the single
 * `data-img` attribute so nothing breaks for products with one photo.
 */
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('productModal');
  const closeModal = document.getElementById('closeModal');
  const isLoggedIn = window.isLoggedIn === true;

  const imgContainer = document.getElementById('modalImageContainer');
  const imgTrack = document.getElementById('modalImageTrack');
  const prevBtn = document.getElementById('modalImgPrev');
  const nextBtn = document.getElementById('modalImgNext');
  const dotsWrap = document.getElementById('modalImageDots');

  let currentIndex = 0;
  let slideCount = 1;

  function updateDots() {
    if (!dotsWrap) return;
    Array.from(dotsWrap.children).forEach((dot, i) => {
      dot.classList.toggle('active', i === currentIndex);
    });
  }

  function updateNavState() {
    if (prevBtn) prevBtn.disabled = currentIndex === 0;
    if (nextBtn) nextBtn.disabled = currentIndex >= slideCount - 1;
  }

  function goToSlide(index) {
    if (!imgTrack) return;
    slideCount = imgTrack.children.length;
    currentIndex = Math.max(0, Math.min(index, slideCount - 1));
    const slideWidth = imgTrack.clientWidth;
    imgTrack.scrollTo({ left: currentIndex * slideWidth, behavior: 'smooth' });
    updateDots();
    updateNavState();
  }

  function buildGallery(images) {
    if (!imgTrack) return;

    imgTrack.innerHTML = '';
    images.forEach((src) => {
      const slide = document.createElement('div');
      slide.className = 'modal-image-slide';
      const img = document.createElement('img');
      img.src = src;
      img.alt = 'Product image';
      slide.appendChild(img);
      imgTrack.appendChild(slide);
    });

    if (dotsWrap) {
      dotsWrap.innerHTML = '';
      images.forEach((_, i) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'modal-image-dot';
        dot.setAttribute('aria-label', `Go to image ${i + 1}`);
        dot.addEventListener('click', () => goToSlide(i));
        dotsWrap.appendChild(dot);
      });
    }

    slideCount = images.length;
    currentIndex = 0;
    imgTrack.scrollLeft = 0;

    if (imgContainer) imgContainer.classList.toggle('has-gallery', images.length > 1);

    updateDots();
    updateNavState();
  }

  if (prevBtn) prevBtn.addEventListener('click', () => goToSlide(currentIndex - 1));
  if (nextBtn) nextBtn.addEventListener('click', () => goToSlide(currentIndex + 1));

  // Keep dots/arrows in sync when the user swipes/drags the track manually
  if (imgTrack) {
    let scrollTimeout;
    imgTrack.addEventListener('scroll', () => {
      clearTimeout(scrollTimeout);
      scrollTimeout = setTimeout(() => {
        const slideWidth = imgTrack.clientWidth || 1;
        currentIndex = Math.round(imgTrack.scrollLeft / slideWidth);
        updateDots();
        updateNavState();
      }, 80);
    });
  }

  document.querySelectorAll('.view-details-btn').forEach(button => {
    button.addEventListener('click', (e) => {
      e.preventDefault();

      if (!isLoggedIn) {
        // Same treatment as the guest Add to Cart prompt: once they log
        // in, send them to the shop page rather than back to wherever
        // View Details happened to be clicked from.
        if (typeof window.openAuthModal === 'function') {
          window.openAuthModal('login', '/TimosaTech/pages/shop.php');
        } else {
          const authOverlay = document.getElementById('authOverlay');
          if (authOverlay) {
            authOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
          }
        }
        return;
      }

      modal.dataset.currentProductId = button.dataset.id;

      const modalTitle    = document.getElementById('modalTitle');
      const modalPrice    = document.getElementById('modalPrice');
      const modalCategory = document.getElementById('modalCategory');
      const modalStock    = document.getElementById('modalStock');
      const modalDesc     = document.getElementById('modalDesc');

      if (modalTitle)    modalTitle.textContent = button.dataset.name;
      if (modalPrice)    modalPrice.textContent = button.dataset.price;
      if (modalCategory) modalCategory.textContent = button.dataset.category;
      if (modalStock)    modalStock.textContent = button.dataset.stock;
      if (modalDesc)     modalDesc.textContent = button.dataset.desc;

      let images = [];
      if (button.dataset.images) {
        try {
          const parsed = JSON.parse(button.dataset.images);
          if (Array.isArray(parsed) && parsed.length > 0) {
            images = parsed;
          }
        } catch (err) {
          images = [];
        }
      }
      if (images.length === 0 && button.dataset.img) {
        images = [button.dataset.img];
      }

      buildGallery(images);
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