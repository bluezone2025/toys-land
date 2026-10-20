const isRTL = document.documentElement.dir === 'rtl';
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

async function post(url, data = {}) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(data),
    });
    if (!res.ok) throw new Error(res.status);
    return res.json();
}

function setBadges(selector, value) {
    document.querySelectorAll(selector).forEach(el => (el.innerText = value));
}

window.showToast = function (msg) {
    const toast = document.getElementById('toast');
    const msgEl = document.getElementById('toast-message');
    if (!toast || !msgEl) return;
    msgEl.innerText = msg;
    toast.classList.remove('translate-y-24', 'opacity-0');
    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(() => toast.classList.add('translate-y-24', 'opacity-0'), 3000);
};

window.openMobileDrawer = function () {
    const drawer = document.getElementById('mobile-drawer');
    drawer.classList.remove('translate-x-full', '-translate-x-full');
    drawer.classList.add('translate-x-0');
    document.getElementById('drawer-overlay').classList.remove('hidden');
};

window.closeMobileDrawer = function () {
    const drawer = document.getElementById('mobile-drawer');
    drawer.classList.remove('translate-x-0');
    drawer.classList.add(isRTL ? 'translate-x-full' : '-translate-x-full');
    document.getElementById('drawer-overlay').classList.add('hidden');
};

window.scrollSlider = function (sliderId, direction) {
    const container = document.getElementById(sliderId);
    if (!container) return;
    // Move by one card so cards always land fully in view.
    const card = container.firstElementChild;
    const step = card ? card.getBoundingClientRect().width + parseFloat(getComputedStyle(container).columnGap || 0) : 320;
    container.scrollBy({ left: (isRTL ? direction : -direction) * step, behavior: 'smooth' });
};

window.toggleFavorite = async function (id, event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    try {
        const data = await post(window.storeRoutes.favorite, { product_id: id });
        document.querySelectorAll('.fav-icon-' + id).forEach(icon => {
            icon.className = 'fav-icon-' + id + ' ' + (data.active ? 'fa-solid text-pink-500' : 'fa-regular text-slate-400') + ' fa-heart text-sm';
        });
        setBadges('[data-fav-count]', data.count);
        showToast(data.message);
        document.dispatchEvent(new CustomEvent('favorites:updated', { detail: data }));
    } catch (e) {
        showToast(isRTL ? 'صار خطأ، حاول مرة ثانية' : 'Something went wrong, please try again');
    }
};

/** Animate a copy of the product image flying into the header cart icon. */
function flyToCart(img) {
    const target = document.querySelector('[data-cart-icon]');
    if (!img || !target || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return Promise.resolve();
    const from = img.getBoundingClientRect();
    const to = target.getBoundingClientRect();
    const clone = img.cloneNode();
    clone.className = 'fly-to-cart';
    Object.assign(clone.style, { left: from.left + 'px', top: from.top + 'px', width: from.width + 'px', height: from.height + 'px' });
    document.body.appendChild(clone);
    requestAnimationFrame(() => {
        const dx = to.left + to.width / 2 - (from.left + from.width / 2);
        const dy = to.top + to.height / 2 - (from.top + from.height / 2);
        clone.style.transform = `translate(${dx}px, ${dy}px) scale(0.08) rotate(${isRTL ? -25 : 25}deg)`;
        clone.style.opacity = '0.4';
    });
    return new Promise(resolve => setTimeout(() => { clone.remove(); resolve(); }, 760));
}

function celebrateCart(count) {
    setBadges('[data-cart-count]', count);
    document.querySelectorAll('[data-cart-count]').forEach(b => { b.classList.remove('badge-bump'); void b.offsetWidth; b.classList.add('badge-bump'); });
    const icon = document.querySelector('[data-cart-icon]');
    if (icon) { icon.classList.remove('cart-shake'); void icon.offsetWidth; icon.classList.add('cart-shake'); }
}

function markButtonAdded(button) {
    if (!button) return;
    const html = button.innerHTML;
    button.disabled = true;
    button.classList.add('btn-added');
    button.innerHTML = '<i class="fa-solid fa-check"></i> ' + (isRTL ? 'انضافت!' : 'Added!');
    setTimeout(() => { button.innerHTML = html; button.classList.remove('btn-added'); button.disabled = false; }, 1600);
}

async function addToCart(payload, button, img) {
    try {
        const animation = flyToCart(img);
        const res = await fetch(window.storeRoutes.cartAdd, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, ...(payload instanceof FormData ? {} : { 'Content-Type': 'application/json' }) },
            body: payload instanceof FormData ? payload : JSON.stringify(payload),
        });
        const data = await res.json();
        await animation;
        if (!res.ok) { showToast(data.message || (isRTL ? 'صار خطأ، حاول مرة ثانية' : 'Something went wrong')); return; }
        celebrateCart(data.count);
        markButtonAdded(button);
        showToast(data.message);
    } catch (e) {
        showToast(isRTL ? 'صار خطأ، حاول مرة ثانية' : 'Something went wrong, please try again');
    }
}

window.quickAddToCart = function (id, button) {
    const img = button?.closest('.toy-card')?.querySelector('img');
    addToCart({ product_id: id, qty: 1 }, button, img);
};

window.dismissCartReminder = function () {
    const el = document.getElementById('cart-reminder');
    if (el) { el.style.transition = 'opacity .3s, transform .3s'; el.style.opacity = '0'; el.style.transform = 'translateY(40px)'; setTimeout(() => el.remove(), 300); }
};

window.closeWelcomeModal = function () {
    const modal = document.getElementById('welcome-modal');
    if (!modal) return;
    modal.style.opacity = '0';
    modal.style.visibility = 'hidden';
    setTimeout(() => modal.remove(), 300);
    try { localStorage.setItem('kids_welcomed', document.body.dataset.welcomeVersion || 'true'); } catch (e) {}
};

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('welcome-modal');
    if (modal) {
        let seen = null;
        try { seen = localStorage.getItem('kids_welcomed'); } catch (e) {}
        if (seen === (document.body.dataset.welcomeVersion || 'true')) modal.remove();
        else setTimeout(() => {
            // Shown a moment after load so it never delays the page's first paint.
            modal.classList.remove('hidden');
            const video = modal.querySelector('video');
            const source = video?.querySelector('source[data-src]');
            if (source) { source.src = source.dataset.src; video.load(); video.play().catch(() => {}); }
        }, 3000);
    }

    // Hero slider
    const slides = document.querySelectorAll('#promo-hero-slider .hero-slide');
    if (slides.length > 1) {
        let current = 0;
        window.changeHeroSlide = function (step) {
            slides[current].classList.replace('slide-active', 'slide-hidden');
            current = (current + step + slides.length) % slides.length;
            slides[current].classList.replace('slide-hidden', 'slide-active');
        };
        setInterval(() => window.changeHeroSlide(1), 5000);
    } else {
        window.changeHeroSlide = () => {};
    }

    // Product page: add to cart without leaving the page.
    const productForm = document.getElementById('product-form');
    if (productForm) {
        productForm.addEventListener('submit', e => {
            e.preventDefault();
            addToCart(new FormData(productForm), productForm.querySelector('button[type=submit]'), document.getElementById('main-image'));
        });
    }

    // Product gallery lightbox
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        const images = JSON.parse(lightbox.dataset.images || '[]');
        const img = document.getElementById('lightbox-img');
        const counter = document.getElementById('lightbox-counter');
        const thumbs = lightbox.querySelectorAll('[data-thumb]');
        let index = 0;
        const show = i => {
            index = (i + images.length) % images.length;
            img.style.opacity = '0';
            setTimeout(() => { img.src = images[index]; img.style.opacity = '1'; }, 120);
            counter.innerText = (index + 1) + ' / ' + images.length;
            thumbs.forEach((t, n) => t.classList.toggle('ring-4', n === index));
        };
        window.openLightbox = i => { show(i || 0); lightbox.classList.remove('hidden'); lightbox.classList.add('flex'); document.body.style.overflow = 'hidden'; };
        window.closeLightbox = () => { lightbox.classList.add('hidden'); lightbox.classList.remove('flex'); document.body.style.overflow = ''; };
        // In RTL "next" sits on the left, so arrows follow reading direction.
        window.lightboxStep = step => show(index + step);
        thumbs.forEach((t, n) => t.addEventListener('click', () => show(n)));
        lightbox.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });
        document.addEventListener('keydown', e => {
            if (lightbox.classList.contains('hidden')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowRight') show(index + (isRTL ? -1 : 1));
            if (e.key === 'ArrowLeft') show(index + (isRTL ? 1 : -1));
        });
        let startX = null;
        img.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
        img.addEventListener('touchend', e => {
            if (startX === null) return;
            const dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 40) show(index + ((dx < 0) !== isRTL ? 1 : -1));
            startX = null;
        });
    }

    const flash = document.body.dataset.flash;
    if (flash) setTimeout(() => showToast(flash), 200);
});
