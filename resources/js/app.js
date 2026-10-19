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
    if (container) container.scrollBy({ left: (isRTL ? direction : -direction) * 320, behavior: 'smooth' });
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

window.quickAddToCart = async function (id) {
    try {
        const data = await post(window.storeRoutes.cartAdd, { product_id: id, qty: 1 });
        setBadges('[data-cart-count]', data.count);
        showToast(data.message);
    } catch (e) {
        showToast(isRTL ? 'صار خطأ، حاول مرة ثانية' : 'Something went wrong, please try again');
    }
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
        else modal.classList.remove('hidden');
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

    const flash = document.body.dataset.flash;
    if (flash) setTimeout(() => showToast(flash), 200);
});
