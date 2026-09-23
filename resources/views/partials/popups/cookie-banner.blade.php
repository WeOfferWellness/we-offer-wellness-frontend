<div class="wow-cookie-banner" id="wowCookieBanner" data-cookie-banner data-wow-popup="cookie-banner" hidden aria-hidden="true">
  <div class="wow-cookie-banner__panel" role="dialog" aria-modal="true" aria-labelledby="wowCookieTitle">
    <div class="wow-cookie-banner__simple" data-cookie-simple>
      <p class="wow-cookie-banner__eyebrow">Your privacy</p>
      <h2 id="wowCookieTitle">We use cookies to keep things calm</h2>
      <p>Cookies help us keep your account secure, understand what’s working and personalise rituals. Pick what suits you.</p>
      <div class="wow-cookie-banner__actions actions">
        <button type="button" class="wow-cookie-btn wow-cookie-btn--primary" data-cookie-open-preferences>Cookie preferences</button>
        <button type="button" class="wow-cookie-btn" data-cookie-reject>Decline</button>
        <button type="button" class="wow-cookie-btn wow-cookie-btn--accept" data-cookie-accept>Accept</button>
      </div>
    </div>

    <div class="wow-cookie-banner__advanced" data-cookie-advanced hidden aria-hidden="true">
      <div class="wow-cookie-banner__head">
        <div>
          <p class="wow-cookie-banner__eyebrow">Fine tune</p>
          <h3>Your cookie preferences</h3>
          <p>Toggle the options below. We’ll remember for 12 months, but you can come back anytime from the footer.</p>
        </div>
        <button type="button" class="wow-cookie-banner__back" data-cookie-back aria-label="Back to simple choices">←</button>
      </div>
      <div class="wow-cookie-banner__toggles">
        <article class="wow-cookie-toggle">
          <div>
            <p class="wow-cookie-toggle__title">Essential</p>
            <p class="wow-cookie-toggle__copy">Security, session stability, checkout. Always on.</p>
          </div>
          <span class="wow-cookie-toggle__badge">Required</span>
        </article>
        <article class="wow-cookie-toggle" data-cookie-option>
          <div>
            <p class="wow-cookie-toggle__title">Analytics</p>
            <p class="wow-cookie-toggle__copy">Helps us understand performance across the site.</p>
          </div>
          <button type="button" class="wow-cookie-switch" data-cookie-toggle="analytics" aria-pressed="false" aria-label="Toggle analytics cookies">
            <span class="wow-cookie-switch__handle"></span>
          </button>
        </article>
        <article class="wow-cookie-toggle" data-cookie-option>
          <div>
            <p class="wow-cookie-toggle__title">Personalisation</p>
            <p class="wow-cookie-toggle__copy">Remembers your goals, location and vibes for better picks.</p>
          </div>
          <button type="button" class="wow-cookie-switch" data-cookie-toggle="personalization" aria-pressed="false" aria-label="Toggle personalisation cookies">
            <span class="wow-cookie-switch__handle"></span>
          </button>
        </article>
        <article class="wow-cookie-toggle" data-cookie-option>
          <div>
            <p class="wow-cookie-toggle__title">Marketing</p>
            <p class="wow-cookie-toggle__copy">Keeps launches relevant across email, ads and social.</p>
          </div>
          <button type="button" class="wow-cookie-switch" data-cookie-toggle="marketing" aria-pressed="false" aria-label="Toggle marketing cookies">
            <span class="wow-cookie-switch__handle"></span>
          </button>
        </article>
      </div>
      <div class="wow-cookie-banner__advanced-actions actions">
        <button type="button" class="wow-cookie-btn wow-cookie-btn--primary" data-cookie-save>Save choices</button>
        <button type="button" class="wow-cookie-btn" data-cookie-reject>Reject non-essential</button>
        <button type="button" class="wow-cookie-btn wow-cookie-btn--accept" data-cookie-accept>Accept all</button>
      </div>
    </div>
  </div>
</div>

<style>
  .wow-cookie-banner{ position:fixed; left:20px; bottom:20px; z-index:1200; width: min(420px, calc(100% - 32px)); font-family:'Manrope',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; }
  .wow-cookie-banner[hidden]{ display:none !important; }
  .wow-cookie-banner__panel{ background:#fff; color:#0b1220; border-radius:3px; border:1px solid rgba(15,23,42,.12); box-shadow:0 30px 80px rgba(15,23,42,.18); padding:24px; }
  .wow-cookie-banner__eyebrow{ text-transform:uppercase; letter-spacing:.24em; font-size:11px; color:#64748b; margin:0 0 8px; }
  .wow-cookie-banner__simple h2{ margin:0 0 8px; font-size:1.35rem; }
  .wow-cookie-banner__simple p{ margin:0 0 16px; font-size:12px; color:#475569; }
  .wow-cookie-banner__actions.actions,
  .wow-cookie-banner__advanced-actions.actions{ display:flex; gap:10px; flex-wrap:wrap; }
  .wow-cookie-banner__actions .wow-cookie-btn,
  .wow-cookie-banner__advanced-actions .wow-cookie-btn{ flex:1 1 auto; min-width:110px; }
  .wow-cookie-banner__advanced{ display:flex; flex-direction:column; gap:18px; }
  .wow-cookie-banner__advanced[hidden]{ display:none !important; }
  .wow-cookie-banner__head{ display:flex; gap:12px; justify-content:space-between; align-items:flex-start; }
  .wow-cookie-banner__head h3{ margin:0 0 6px; }
  .wow-cookie-banner__head p{ margin:0; color:#475569; }
  .wow-cookie-banner__back{ border:none; background:#f1f5f9; color:#0b1220; width:32px; height:32px; border-radius:50%; cursor:pointer; }
  .wow-cookie-banner__toggles{ display:flex; flex-direction:column; gap:12px; }
  .wow-cookie-toggle{ border:1px solid rgba(15,23,42,.12); border-radius:3px; padding:14px; background:#fff; display:flex; justify-content:space-between; gap:16px; }
  .wow-cookie-toggle__title{ margin:0 0 4px; font-weight:600; }
  .wow-cookie-toggle__copy{ margin:0; color:#475569; font-size:.9rem; }
  .wow-cookie-toggle__badge{ align-self:center; font-size:.75rem; letter-spacing:.2em; text-transform:uppercase; color:#16a34a; border:1px solid rgba(22,163,74,.35); border-radius:999px; padding:3px 10px; }
  .wow-cookie-switch{ width:46px; height:24px; border-radius:999px; border:1px solid rgba(15,23,42,.2); background:#e2e8f0; position:relative; cursor:pointer; transition:background .2s ease,border-color .2s ease; flex-shrink:0; }
  .wow-cookie-switch__handle{ position:absolute; inset:2px; width:18px; height:18px; border-radius:50%; background:#fff; box-shadow:0 2px 6px rgba(15,23,42,.2); transition:transform .2s ease; display:block; }
  .wow-cookie-switch[aria-pressed="true"]{ background:#c1f0cb; border-color:#22c55e; }
  .wow-cookie-switch[aria-pressed="true"] .wow-cookie-switch__handle{ transform:translateX(22px); }
  .wow-cookie-banner__advanced-actions{ display:flex; flex-wrap:wrap; gap:10px; }
  .wow-cookie-btn{ height:36px; border-radius:3px; font-size:16px; font-weight:400; border:1px solid rgba(16,24,40,.22); background:#fff; color:rgba(11,18,32,.82); cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 10px 22px rgba(16,24,40,.08); padding:0 18px; transition:background .2s ease, color .2s ease, border-color .2s ease; }
  .wow-cookie-btn:hover,
  .wow-cookie-btn:focus-visible{ background:#000; color:#fff; border-color:#000; outline:none; }
  .wow-cookie-btn--primary{ background:#0b1220; color:#fff; border-color:#0b1220; box-shadow:0 12px 28px rgba(11,18,32,.18); }
  .wow-cookie-btn--accept{ background:#549483; color:#fff; border-color:#549483; }
  .wow-cookie-btn--accept:hover,
  .wow-cookie-btn--accept:focus-visible{ background:#3f6f61; border-color:#3f6f61; }
  @media (max-width:640px){
    .wow-cookie-banner{ left:16px; right:16px; width:auto; }
  }
</style>

<script>
(function(){
  const STORAGE_KEY = 'wow_cookie_preferences';
  const COOKIE_KEY = 'wow_cookie_preferences';
  const VERSION = 1;
  const banner = document.getElementById('wowCookieBanner');
  if (!banner) return;

  const toggles = banner.querySelectorAll('[data-cookie-toggle]');
  const acceptAllButtons = banner.querySelectorAll('[data-cookie-accept]');
  const rejectButtons = banner.querySelectorAll('[data-cookie-reject]');
  const saveButtons = banner.querySelectorAll('[data-cookie-save]');
  const openPreferencesButtons = banner.querySelectorAll('[data-cookie-open-preferences]');
  const backButton = banner.querySelector('[data-cookie-back]');
  const simpleView = banner.querySelector('[data-cookie-simple]');
  const advancedView = banner.querySelector('[data-cookie-advanced]');

  function defaults(){
    return { necessary: true, analytics: false, personalization: false, marketing: false, version: VERSION, updated_at: null };
  }

  function readPrefs(){
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return defaults();
      const parsed = JSON.parse(raw);
      if (!parsed || parsed.version !== VERSION) return defaults();
      return Object.assign(defaults(), parsed);
    } catch (_err) {
      return defaults();
    }
  }

  function persist(prefs){
    const payload = Object.assign({}, prefs, { version: VERSION, updated_at: new Date().toISOString() });
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(payload)); } catch (_err) {}
    try {
      document.cookie = COOKIE_KEY + '=' + encodeURIComponent(JSON.stringify({
        version: VERSION,
        analytics: !!payload.analytics,
        personalization: !!payload.personalization,
        marketing: !!payload.marketing,
      })) + '; Path=/; Max-Age=' + (60*60*24*365) + '; SameSite=Lax';
    } catch (_err) {}
    try { document.dispatchEvent(new CustomEvent('wow:cookie-preferences', { detail: payload })); } catch (_err) {}
    return payload;
  }

  function applyToUI(prefs){
    toggles.forEach((btn) => {
      const key = btn.getAttribute('data-cookie-toggle');
      if (!key) return;
      btn.setAttribute('aria-pressed', prefs[key] ? 'true' : 'false');
    });
  }

  function preferencesFromUI(){
    const prefs = defaults();
    toggles.forEach((btn) => {
      const key = btn.getAttribute('data-cookie-toggle');
      if (!key) return;
      prefs[key] = btn.getAttribute('aria-pressed') === 'true';
    });
    return prefs;
  }

  function setAllOptional(value){
    toggles.forEach((btn) => btn.setAttribute('aria-pressed', value ? 'true' : 'false'));
  }

  function showBanner(){
    banner.hidden = false;
    banner.setAttribute('aria-hidden', 'false');
  }

  function hideBanner(){
    banner.hidden = true;
    banner.setAttribute('aria-hidden', 'true');
    document.dispatchEvent(new CustomEvent('wow:popup-closed', { detail: { key: 'cookie-banner' } }));
  }

  function openAdvanced(){
    advancedView?.removeAttribute('hidden');
    advancedView?.setAttribute('aria-hidden', 'false');
    simpleView?.setAttribute('hidden', 'true');
  }

  function closeAdvanced(){
    advancedView?.setAttribute('hidden', 'true');
    advancedView?.setAttribute('aria-hidden', 'true');
    simpleView?.removeAttribute('hidden');
  }

  toggles.forEach((btn) => {
    btn.addEventListener('click', () => {
      const current = btn.getAttribute('aria-pressed') === 'true';
      btn.setAttribute('aria-pressed', current ? 'false' : 'true');
    });
  });

  acceptAllButtons.forEach((btn) => btn.addEventListener('click', () => {
    setAllOptional(true);
    persist(preferencesFromUI());
    hideBanner();
  }));

  rejectButtons.forEach((btn) => btn.addEventListener('click', () => {
    setAllOptional(false);
    persist(preferencesFromUI());
    hideBanner();
  }));

  saveButtons.forEach((btn) => btn.addEventListener('click', () => {
    persist(preferencesFromUI());
    hideBanner();
  }));

  openPreferencesButtons.forEach((btn) => {
    const handler = () => { applyToUI(readPrefs()); openAdvanced(); };
    btn.addEventListener('click', handler);
    if (btn.tagName !== 'BUTTON') {
      btn.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          handler();
        }
      });
    }
  });

  backButton?.addEventListener('click', closeAdvanced);

  function shouldPrompt(){
    const prefs = readPrefs();
    applyToUI(prefs);
    return !prefs.updated_at;
  }

  function openPreferences(){
    applyToUI(readPrefs());
    showBanner();
    openAdvanced();
  }

  window.WOWCookiePreferences = { open: openPreferences };

  document.querySelectorAll('[data-cookie-preferences-trigger]').forEach((btn) => {
    btn.addEventListener('click', (event) => {
      event.preventDefault?.();
      openPreferences();
    });
  });

  window.WOWCookieBanner = {
    open(){ applyToUI(readPrefs()); closeAdvanced(); showBanner(); },
    openPreferences,
    close: hideBanner,
    shouldPrompt,
  };
})();
</script>
