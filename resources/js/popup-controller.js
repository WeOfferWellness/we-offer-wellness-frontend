const backendUrl = String(import.meta.env.VITE_BACKEND_URL || 'https://studio.weofferwellness.co.uk').replace(/\/$/, '');
const storagePrefix = 'wow_popup_';

function readStorage(storage, key) {
    try { return storage.getItem(`${storagePrefix}${key}`); } catch (_) { return null; }
}

function writeStorage(storage, key, value) {
    try { storage.setItem(`${storagePrefix}${key}`, String(value)); } catch (_) {}
}

function sessionId() {
    let id = readStorage(sessionStorage, 'session_id');
    if (!id) {
        id = globalThis.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        writeStorage(sessionStorage, 'session_id', id);
    }
    return id;
}

function visitorId() {
    const name = 'wow_visitor_id';
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
    if (match?.[1]) return decodeURIComponent(match[1]);
    const id = globalThis.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    document.cookie = `${name}=${encodeURIComponent(id)}; Max-Age=315360000; Path=/; SameSite=Lax`;
    return id;
}

function cookieValue(name) {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}=([^;]*)`));
    return match ? decodeURIComponent(match[1]) : '';
}

function pageIsEligible(config) {
    const path = window.location.pathname.replace(/^\//, '');
    const matches = (config.pages || ['*']).some((pattern) => {
        const normalized = String(pattern || '*').replace(/^\//, '');
        if (normalized === '*') return true;
        const escaped = normalized.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\\\*/g, '.*');
        return new RegExp(`^${escaped}$`).test(path);
    });
    const excluded = (config.excluded_pages || []).some((pattern) => {
        const normalized = String(pattern || '').replace(/^\//, '');
        const escaped = normalized.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\\\*/g, '.*');
        return new RegExp(`^${escaped}$`).test(path);
    });
    return matches && !excluded;
}

function frequencyAllows(config) {
    const key = config.key;
    const frequency = config.frequency || 'once_session';
    const last = Number(readStorage(localStorage, `${key}:last`) || 0);
    const cooldown = Number(config.cooldown_hours || 0) * 3600000;
    if (cooldown && Date.now() - last < cooldown) return false;
    const closedAt = Number(readStorage(localStorage, `${key}:closed`) || 0);
    const waitAfterClose = Number(config.wait_after_close_seconds || 0) * 1000;
    if (waitAfterClose && Date.now() - closedAt < waitAfterClose) return false;
    if (frequency === 'once_session' && readStorage(sessionStorage, `${key}:seen`)) return false;
    if (frequency === 'once_day') {
        const day = new Date().toISOString().slice(0, 10);
        if (readStorage(localStorage, `${key}:day`) === day) return false;
    }
    if (frequency === 'once_visitor' && readStorage(localStorage, `${key}:visitor`)) return false;
    if (key === 'cookie-banner' && cookieValue('wow_cookie_preferences')) return false;
    if (key === 'location-banner' && cookieValue('wow_location_prompt_v2') === '1') return false;
    if (key === 'newsletter-modal' && window.WOWNewsletterModal?.isSubscribed?.()) return false;
    return true;
}

function markShown(config) {
    const key = config.key;
    writeStorage(localStorage, `${key}:last`, Date.now());
    if (config.frequency === 'once_session') writeStorage(sessionStorage, `${key}:seen`, '1');
    if (config.frequency === 'once_day') writeStorage(localStorage, `${key}:day`, new Date().toISOString().slice(0, 10));
    if (config.frequency === 'once_visitor') writeStorage(localStorage, `${key}:visitor`, '1');
}

function track(config, event, metadata = {}) {
    fetch(`${backendUrl}/api/popups/${encodeURIComponent(config.key)}/interactions`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ event, session_id: sessionId(), visitor_id: visitorId(), page_url: window.location.href, metadata }),
        keepalive: true,
    }).catch(() => {});
}

function showElement(element) {
    element.hidden = false;
    element.setAttribute('aria-hidden', 'false');
}

function hideElement(element) {
    element.hidden = true;
    element.setAttribute('aria-hidden', 'true');
}

function locationController(element) {
    if (element.dataset.popupBound === 'true') return;
    element.dataset.popupBound = 'true';
    const error = element.querySelector('[data-wow-location-error]');
    const allow = element.querySelector('[data-wow-location-allow]');
    const skip = element.querySelector('[data-wow-location-skip]');
    const cookieSet = (name, value) => { document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=315360000; Path=/; SameSite=Lax`; };
    const close = () => { hideElement(element); document.dispatchEvent(new CustomEvent('wow:popup-closed', { detail: { key: 'location-banner' } })); };
    const seen = () => { cookieSet('wow_location_prompt_v2', '1'); cookieSet('wow_geo_done', '1'); };
    skip?.addEventListener('click', () => { seen(); close(); });
    allow?.addEventListener('click', () => {
        if (!navigator.geolocation) { error.textContent = 'Geolocation is not available in this browser.'; error.hidden = false; return; }
        allow.disabled = true;
        navigator.geolocation.getCurrentPosition(async (position) => {
            const { latitude: lat, longitude: lng } = position.coords;
            let city = '', region = '', country = '';
            try {
                const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json`);
                if (window.WOW_MAPS_KEY) {
                    url.searchParams.set('access_token', window.WOW_MAPS_KEY);
                    url.searchParams.set('limit', '1');
                    const feature = (await (await fetch(url)).json())?.features?.[0];
                    const context = feature?.context || [];
                    city = context.find((item) => item.id?.startsWith('place'))?.text || context.find((item) => item.id?.startsWith('locality'))?.text || '';
                    region = context.find((item) => item.id?.startsWith('region'))?.text || '';
                    country = context.find((item) => item.id?.startsWith('country'))?.text || '';
                }
            } catch (_) {}
            try {
                await fetch('/api/geo', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: JSON.stringify({ lat, lng, city, region, country, mode: 'mixed' }) });
            } catch (_) {}
            cookieSet('wow_lat', lat); cookieSet('wow_lng', lng); cookieSet('wow_location', JSON.stringify({ lat, lng, city, region, country }));
            seen(); close(); window.location.reload();
        }, () => { allow.disabled = false; error.textContent = 'We couldn’t get your location.'; error.hidden = false; }, { enableHighAccuracy: false, timeout: 6000, maximumAge: 60000 });
    });
}

function openPopup(config, element) {
    if (config.key === 'newsletter-modal' && window.WOWNewsletterModal?.open) window.WOWNewsletterModal.open(true);
    else if (config.key === 'cookie-banner' && window.WOWCookieBanner?.open) window.WOWCookieBanner.open();
    else { locationController(element); showElement(element); }
    markShown(config);
    track(config, 'open');
}

function initPopupController() {
    const elements = new Map([...document.querySelectorAll('[data-wow-popup]')].map((element) => [element.dataset.wowPopup, element]));
    if (!elements.size || document.documentElement.dataset.wowPopupsInitialized === 'true') return;
    document.documentElement.dataset.wowPopupsInitialized = 'true';
    const active = { key: null };
    const firstVisit = !readStorage(localStorage, 'has_visited');
    const queue = [];
    const tryNext = () => {
        if (active.key) return;
        const next = queue.find((config) => elements.has(config.key) && frequencyAllows(config));
        if (!next) return;
        active.key = next.key;
        window.setTimeout(() => openPopup(next, elements.get(next.key)), Math.max(0, Number(next.delay_seconds || 0) * 1000));
    };
    const enqueue = (config) => { if (!queue.some((item) => item.key === config.key)) { queue.push(config); queue.sort((a, b) => Number(a.priority) - Number(b.priority)); tryNext(); } };
    document.addEventListener('wow:popup-closed', (event) => {
        const key = event.detail?.key;
        if (!key) return;
        writeStorage(localStorage, `${key}:closed`, Date.now());
        if (key === active.key) { active.key = null; window.setTimeout(tryNext, 100); }
    });
    fetch(`${backendUrl}/api/popups?${new URLSearchParams({ page_url: window.location.href })}`, { credentials: 'include', headers: { Accept: 'application/json' } })
        .then((response) => response.ok ? response.json() : { data: [] })
        .then((payload) => {
            (payload.data || []).forEach((config) => {
                const element = elements.get(config.key);
                if (!element || !frequencyAllows(config)) return;
                const triggers = config.triggers || {};
                if (triggers.first_time_user && !firstVisit) return;
                if (triggers.on_load) enqueue(config);
                if (triggers.scroll) {
                    const onScroll = () => {
                        const max = Math.max(document.documentElement.scrollHeight - window.innerHeight, 1);
                        if ((window.scrollY / max) * 100 >= Number(triggers.scroll_percent || 50)) {
                            enqueue(config);
                            window.removeEventListener('scroll', onScroll);
                        }
                    };
                    window.addEventListener('scroll', onScroll, { passive: true });
                    window.addEventListener('resize', onScroll, { passive: true });
                    window.requestAnimationFrame(onScroll);
                }
                if (triggers.exit_intent) document.addEventListener('mouseout', (event) => { if (event.clientY <= 0) enqueue(config); }, { once: true });
                track(config, 'impression');
            });
            writeStorage(localStorage, 'has_visited', '1');
        })
        .catch(() => {});
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initPopupController, { once: true });
else initPopupController();

export { initPopupController };
