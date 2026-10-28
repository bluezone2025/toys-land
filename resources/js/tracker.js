/**
 * ToyslandTracker: unified GA4 e-commerce dataLayer + browser pixels.
 *
 * - Every event gets an event_id (UUID v4). Events the server already recorded (product view,
 *   add to cart, checkout, purchase…) arrive with the server's id, so the browser pixel and the
 *   server-side Conversions API copy are deduplicated by the ad platforms.
 * - Browser-only interactions (list impressions, product clicks, checkout steps) are batched to
 *   /api/tracking/collect so the admin sees the full journey.
 * - Pixels (Meta, TikTok, Snapchat, GA4) load after the page is idle, never blocking rendering.
 *   With a GTM container in "gtm" mode only the dataLayer is fed and GTM owns the tags.
 */
const config = (() => {
    try { return JSON.parse(document.getElementById('tl-config')?.textContent || '{}'); } catch (e) { return {}; }
})();
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
const pixels = config.pixels || {};
window.dataLayer = window.dataLayer || [];

// GA4 event → [Meta, TikTok, Snapchat] standard event names (same table as App\Support\Tracking).
const PIXEL_EVENTS = {
    view_item: ['ViewContent', 'ViewContent', 'VIEW_CONTENT'],
    add_to_cart: ['AddToCart', 'AddToCart', 'ADD_CART'],
    add_to_wishlist: ['AddToWishlist', 'AddToWishlist', 'ADD_TO_WISHLIST'],
    begin_checkout: ['InitiateCheckout', 'InitiateCheckout', 'START_CHECKOUT'],
    add_payment_info: ['AddPaymentInfo', 'AddPaymentInfo', 'ADD_BILLING'],
    purchase: ['Purchase', 'CompletePayment', 'PURCHASE'],
    search: ['Search', 'Search', 'SEARCH'],
};

export function uuid() {
    if (window.crypto?.randomUUID) return crypto.randomUUID();
    return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, c =>
        (c ^ (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))).toString(16));
}

// ------------------------------------------------------------------ consent

function readCookie(name) {
    return (document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)')) || [])[1];
}

function consentGranted() {
    const c = config.consent || {};
    if (!c.banner) return true;
    const saved = readCookie(c.cookie);
    return saved ? saved === 'granted' : c.default !== 'denied';
}

function setConsent(state) {
    const c = config.consent || {};
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${c.cookie}=${state}; Max-Age=${60 * 60 * 24 * 365}; Path=/; SameSite=Lax${secure}`;
    const value = state === 'granted' ? 'granted' : 'denied';
    window.gtag?.('consent', 'update', { ad_storage: value, analytics_storage: value, ad_user_data: value, ad_personalization: value });
    window.dataLayer.push({ event: 'consent_update', consent_state: value });
    if (state === 'granted') loadAdPixels();
}

function initConsentBanner() {
    const banner = document.getElementById('consent-banner');
    if (!banner) return;
    if (!readCookie(config.consent?.cookie)) setTimeout(() => banner.classList.remove('hidden'), 1200);
    banner.addEventListener('click', e => {
        const button = e.target.closest('[data-consent]');
        if (!button) return;
        setConsent(button.dataset.consent);
        banner.remove();
    });
}

// ------------------------------------------------------------------ pixels

let adPixelsLoaded = false;

function loadScript(src) {
    const s = document.createElement('script');
    s.async = true;
    s.src = src;
    document.head.appendChild(s);
}

// gtag commands are queued in the dataLayer right away (so config precedes any event);
// only the gtag.js download waits for the page to be idle.
if (pixels.ga4) {
    gtag('js', new Date());
    if (config.user?.sha256_email_address || config.user?.sha256_phone_number) {
        gtag('set', 'user_data', { sha256_email_address: config.user.sha256_email_address, sha256_phone_number: config.user.sha256_phone_number });
    }
    gtag('config', pixels.ga4, { currency: config.currency });
}

function loadGa4() {
    if (pixels.ga4) loadScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(pixels.ga4));
}

function loadAdPixels() {
    if (adPixelsLoaded || !consentGranted()) return;
    adPixelsLoaded = true;
    const user = config.user || {};

    if (pixels.meta && !window.fbq) {
        /* eslint-disable */
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];}(window);
        /* eslint-enable */
        loadScript('https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', pixels.meta, { em: user.sha256_email_address, ph: user.sha256_phone_number, external_id: user.external_id });
        fbq('track', 'PageView');
    }

    if (pixels.tiktok && !window.ttq) {
        const ttq = window.ttq = window.ttq || [];
        ttq.methods = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie', 'holdConsent', 'revokeConsent', 'grantConsent'];
        ttq.setAndDefer = (t, e) => { t[e] = function () { t.push([e].concat(Array.prototype.slice.call(arguments, 0))); }; };
        ttq.methods.forEach(m => ttq.setAndDefer(ttq, m));
        ttq.instance = id => { const e = ttq._i[id] || []; ttq.methods.forEach(m => ttq.setAndDefer(e, m)); return e; };
        ttq.load = (id, opts) => {
            ttq._i = ttq._i || {}; ttq._i[id] = []; ttq._i[id]._u = 'https://analytics.tiktok.com/i18n/pixel/events.js';
            ttq._t = ttq._t || {}; ttq._t[id] = +new Date(); ttq._o = ttq._o || {}; ttq._o[id] = opts || {};
            loadScript('https://analytics.tiktok.com/i18n/pixel/events.js?sdkid=' + encodeURIComponent(id) + '&lib=ttq');
        };
        ttq.load(pixels.tiktok);
        if (user.sha256_email_address || user.sha256_phone_number) {
            ttq.identify({ email: user.sha256_email_address, phone_number: user.sha256_phone_number, external_id: user.external_id });
        }
        ttq.page();
    }

    if (pixels.snap && !window.snaptr) {
        const a = window.snaptr = function () { a.handleRequest ? a.handleRequest.apply(a, arguments) : a.queue.push(arguments); };
        a.queue = [];
        loadScript('https://sc-static.net/scevent.min.js');
        snaptr('init', pixels.snap, { user_hashed_email: user.sha256_email_address, user_hashed_phone_number: user.sha256_phone_number });
        snaptr('track', 'PAGE_VIEW');
    }

    // Events raised before the pixels loaded.
    queuedPixelEvents.splice(0).forEach(sendToPixels);
}

const queuedPixelEvents = [];

function sendToPixels(payload) {
    const names = PIXEL_EVENTS[payload.event];
    const ec = payload.ecommerce || {};
    const items = ec.items || [];

    if (pixels.ga4) {
        gtag('event', payload.event, { ...ec, event_id: payload.event_id, search_term: payload.search_term, send_to: pixels.ga4 });
    }
    if (!names) return;
    if (!adPixelsLoaded) { if (consentGranted()) queuedPixelEvents.push(payload); return; }

    const ids = items.map(i => String(i.item_id));
    const qty = items.reduce((n, i) => n + (i.quantity || 1), 0);
    if (window.fbq) {
        fbq('track', names[0], {
            content_ids: ids, content_type: 'product', currency: payload.currency, value: ec.value,
            contents: items.map(i => ({ id: String(i.item_id), quantity: i.quantity || 1, item_price: i.price })),
            num_items: qty || undefined, search_string: payload.search_term, order_id: ec.transaction_id,
        }, { eventID: payload.event_id });
    }
    if (window.ttq?.track) {
        ttq.track(names[1], {
            contents: items.map(i => ({ content_id: String(i.item_id), content_name: i.item_name, price: i.price, quantity: i.quantity || 1 })),
            content_type: 'product', value: ec.value, currency: payload.currency, query: payload.search_term, order_id: ec.transaction_id,
        }, { event_id: payload.event_id });
    }
    if (window.snaptr) {
        snaptr('track', names[2], {
            price: ec.value, currency: payload.currency, item_ids: ids, number_items: qty || undefined,
            transaction_id: ec.transaction_id, search_string: payload.search_term, client_dedup_id: payload.event_id,
        });
    }
}

// ------------------------------------------------------------------ dataLayer + collector

const outbox = [];
let flushTimer = null;

function flush(useBeacon = false) {
    clearTimeout(flushTimer);
    if (!outbox.length || !config.collect) return;
    const body = JSON.stringify({ events: outbox.splice(0, 20), _token: csrf });
    if (useBeacon && navigator.sendBeacon) {
        navigator.sendBeacon(config.collect, new Blob([body], { type: 'application/json' }));
    } else {
        fetch(config.collect, {
            method: 'POST', keepalive: true, credentials: 'same-origin', body,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        }).catch(() => {});
    }
    if (outbox.length) flushTimer = setTimeout(flush, 500);
}

/** Push an event to the dataLayer (and pixels); `collect` also reports it to the store's server. */
export function track(name, params = {}, { eventId = uuid(), collect = false, searchTerm } = {}) {
    const payload = {
        event: name,
        event_id: eventId,
        currency: params.currency || config.currency,
        store_country: config.country,
        ecommerce: { currency: config.currency, ...params },
    };
    if (searchTerm) payload.search_term = searchTerm;
    publish(payload);
    if (collect) {
        outbox.push({ name, event_id: eventId, params, url: location.href });
        clearTimeout(flushTimer);
        flushTimer = setTimeout(flush, 1500);
    }
    return eventId;
}

/** Events the server recorded and handed to the page (already include the shared event_id). */
export function fromServer(payload) {
    if (payload && payload.event) publish(payload);
}

function publish(payload) {
    if (payload.ecommerce) window.dataLayer.push({ ecommerce: null }); // GA4: clear the previous ecommerce object
    window.dataLayer.push({ ...payload, user_data: config.user, page_type: config.page });
    sendToPixels(payload);
}

// ------------------------------------------------------------------ store interactions

function itemFrom(el) {
    try { return JSON.parse(el.dataset.item); } catch (e) { return null; }
}

function listName(el) {
    return el.closest('[data-list-name]')?.dataset.listName || document.title;
}

/** view_item_list: once per list when it scrolls into view, with the cards actually visible. */
function observeLists() {
    const lists = document.querySelectorAll('[data-list-name]');
    if (!lists.length || !('IntersectionObserver' in window)) return;
    const seen = new WeakSet();
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting || seen.has(entry.target)) return;
            seen.add(entry.target);
            observer.unobserve(entry.target);
            const name = entry.target.dataset.listName;
            const items = [...entry.target.querySelectorAll('[data-item]')].slice(0, 20).map((card, index) => ({ ...itemFrom(card), index, item_list_name: name })).filter(i => i.item_id);
            if (items.length) track('view_item_list', { item_list_name: name, items }, { collect: true });
        });
    }, { threshold: 0.3 });
    lists.forEach(list => observer.observe(list));
}

function bindProductClicks() {
    document.addEventListener('click', e => {
        const link = e.target.closest('a[href]');
        const card = link?.closest('[data-item]');
        if (!card) return;
        const item = itemFrom(card);
        if (!item) return;
        const name = listName(card);
        track('select_item', { item_list_name: name, items: [{ ...item, item_list_name: name }] }, { collect: true });
        flush(true); // the page is about to change
    }, true);
}

/** Checkout: live abandoned-checkout capture + add_shipping_info / add_payment_info. */
function bindCheckout() {
    const form = document.getElementById('checkout-form');
    if (!form) return;
    let cart = {};
    try { cart = JSON.parse(document.getElementById('tl-checkout')?.textContent || '{}'); } catch (e) {}
    const field = name => form.elements[name]?.value?.trim() || '';
    const sent = { shipping: '', payment: '' };
    let progressTimer = null;
    let lastProgress = '';

    const values = () => {
        const data = {};
        ['name', 'phone', 'email', 'country', 'city', 'block', 'street', 'avenue', 'property_type', 'apartment', 'floor', 'notes', 'coupon'].forEach(k => { data[k] = field(k); });
        data.payment_method = form.querySelector('input[name=payment_method]:checked')?.value || '';
        data.step = data.payment_method && sent.payment ? 'payment' : (data.city && data.block ? 'address' : 'contact');
        return data;
    };

    const sendProgress = () => {
        const data = values();
        if (data.phone.replace(/\D/g, '').length < 7 && !/.+@.+\..+/.test(data.email)) return;
        const key = JSON.stringify(data);
        if (key === lastProgress || !config.progress) return;
        lastProgress = key;
        fetch(config.progress, {
            method: 'POST', keepalive: true, credentials: 'same-origin', body: key,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        }).catch(() => {});
    };
    const queueProgress = () => { clearTimeout(progressTimer); progressTimer = setTimeout(sendProgress, 900); };

    const shipping = () => {
        const tier = [field('country'), field('city')].filter(Boolean).join(' - ');
        if (!field('city') || !field('block') || !field('street') || sent.shipping === tier) return;
        sent.shipping = tier;
        track('add_shipping_info', { value: cart.value, items: cart.items, shipping_tier: tier, coupon: field('coupon') || undefined }, { collect: true });
    };
    const payment = () => {
        const method = form.querySelector('input[name=payment_method]:checked')?.value;
        const label = { knet: 'KNET', card: 'Credit Card', applepay: 'Apple Pay', cod: 'COD' }[method] || method;
        if (!method || sent.payment === label) return;
        sent.payment = label;
        track('add_payment_info', { value: cart.value, items: cart.items, payment_type: label, coupon: field('coupon') || undefined }, { collect: true });
    };

    form.addEventListener('change', e => {
        if (e.target.name === 'payment_method') payment();
        else shipping();
        queueProgress();
    });
    form.addEventListener('focusout', queueProgress);
    form.addEventListener('submit', () => { shipping(); payment(); flush(true); sendProgress(); });
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') sendProgress(); });
}

// ------------------------------------------------------------------ boot

function whenIdle(fn) {
    const run = () => ('requestIdleCallback' in window ? requestIdleCallback(fn, { timeout: 3000 }) : setTimeout(fn, 1200));
    document.readyState === 'complete' ? run() : window.addEventListener('load', run, { once: true });
}

document.addEventListener('DOMContentLoaded', () => {
    (config.events || []).forEach(fromServer);
    observeLists();
    bindProductClicks();
    bindCheckout();
    initConsentBanner();
});

whenIdle(() => {
    loadGa4();
    loadAdPixels();
});

window.addEventListener('pagehide', () => flush(true));

window.ToyslandTracker = { track, fromServer, uuid, consent: setConsent };
