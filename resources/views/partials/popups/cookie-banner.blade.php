<div class="wow-cookie-banner" id="wowCookieBanner" data-cookie-banner data-wow-popup="cookie-banner" hidden aria-hidden="true">
  <div class="wow-cookie-banner__panel" role="dialog" aria-modal="true" aria-labelledby="wowCookieTitle">
    <div class="wow-cookie-banner__simple" data-cookie-simple>
      <p class="wow-cookie-banner__eyebrow">Your privacy</p>
      <h2 id="wowCookieTitle">We use cookies to keep things calm</h2>
      <p>Cookies help us keep your account secure, understand what’s working and personalise rituals. Pick what suits you.</p>
      <div class="wow-cookie-banner__actions actions">
        <button type="button" class="wow-cookie-btn wow-cookie-btn--soft" data-cookie-open-preferences>Cookie preferences</button>
        <button type="button" class="wow-cookie-btn wow-cookie-btn--quiet" data-cookie-reject>Decline</button>
        <button type="button" class="wow-cookie-btn wow-cookie-btn--primary" data-cookie-accept>Accept</button>
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
        <button type="button" class="wow-cookie-btn wow-cookie-btn--soft" data-cookie-save>Save choices</button>
        <button type="button" class="wow-cookie-btn wow-cookie-btn--quiet" data-cookie-reject>Reject non-essential</button>
        <button type="button" class="wow-cookie-btn wow-cookie-btn--primary" data-cookie-accept>Accept all</button>
      </div>
    </div>
  </div>
</div>

<style>
  .wow-cookie-banner{position:fixed;inset:0;z-index:1200;display:grid;place-items:center;width:100%;padding:20px;background:rgba(11,48,40,.28);backdrop-filter:blur(4px);font-family:'Instrument Sans',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
  .wow-cookie-banner[hidden]{display:none!important}
  .wow-cookie-banner__panel{width:min(560px,100%);max-height:min(760px,calc(100dvh - 40px));overflow:auto;background:#fff;color:#17201d;border:1px solid #dce4e0;border-radius:8px;box-shadow:0 28px 90px rgba(11,48,40,.22);padding:32px}
  .wow-cookie-banner__eyebrow{text-transform:uppercase;letter-spacing:.24em;font-size:11px;color:#4f9482;font-weight:700;margin:0 0 8px}
  .wow-cookie-banner__simple h2{margin:0 0 10px;color:#0b3028;font-family:'Playfair Display',Georgia,serif;font-size:clamp(30px,5vw,42px);font-weight:500;line-height:1;letter-spacing:-.04em}
  .wow-cookie-banner__simple>p:not(.wow-cookie-banner__eyebrow){margin:0 0 22px;color:#68736f;font-size:14px;line-height:1.6}
  .wow-cookie-banner__actions.actions{display:grid;grid-template-columns:1.35fr .85fr .85fr;gap:10px}
  .wow-cookie-banner__advanced-actions.actions{display:grid;grid-template-columns:1fr 1.25fr 1fr;gap:10px}
  .wow-cookie-banner__actions .wow-cookie-btn,.wow-cookie-banner__advanced-actions .wow-cookie-btn{min-width:0}
  .wow-cookie-banner__advanced{display:flex;flex-direction:column;gap:20px}
  .wow-cookie-banner__advanced[hidden]{display:none!important}
  .wow-cookie-banner__head{display:flex;gap:16px;justify-content:space-between;align-items:flex-start}
  .wow-cookie-banner__head h3{margin:0 0 7px;color:#0b3028;font-family:'Playfair Display',Georgia,serif;font-size:30px;font-weight:500;line-height:1;letter-spacing:-.04em}
  .wow-cookie-banner__head p:not(.wow-cookie-banner__eyebrow){margin:0;color:#68736f;font-size:13px;line-height:1.55}
  .wow-cookie-banner__back{display:grid;place-items:center;width:38px;height:38px;flex:0 0 38px;border:1px solid #dce4e0;border-radius:50%;background:#f3f7f5;color:#315e52;font-size:20px;cursor:pointer}
  .wow-cookie-banner__back:hover,.wow-cookie-banner__back:focus-visible{background:#e5f1ed;border-color:#9eafa9;outline:none}
  .wow-cookie-banner__toggles{display:flex;flex-direction:column;gap:10px}
  .wow-cookie-toggle{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 15px;border:1px solid #dfe8e4;border-radius:8px;background:#f7faf9}
  .wow-cookie-toggle__title{margin:0 0 4px;color:#17201d;font-size:13px;font-weight:700}
  .wow-cookie-toggle__copy{margin:0;color:#68736f;font-size:12px;line-height:1.45}
  .wow-cookie-toggle__badge{align-self:center;padding:5px 9px;border:1px solid #b9dacc;border-radius:999px;color:#2f7464;background:#eaf5f1;font-size:9px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;white-space:nowrap}
  .wow-cookie-switch{position:relative;width:46px;height:26px;flex:0 0 46px;border:1px solid #cfd9d5;border-radius:999px;background:#e4ebe8;cursor:pointer;transition:background .2s ease,border-color .2s ease}
  .wow-cookie-switch__handle{position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 2px 6px rgba(15,23,42,.18);transition:transform .2s ease}
  .wow-cookie-switch[aria-pressed="true"]{border-color:#4f9482;background:#4f9482}
  .wow-cookie-switch[aria-pressed="true"] .wow-cookie-switch__handle{transform:translateX(20px)}
  .wow-cookie-btn{display:inline-flex;align-items:center;justify-content:center;height:42px;min-height:42px;padding:0 16px;border:1px solid #cfd9d5;border-radius:999px;background:#fff;color:#17201d;font-size:13px;font-weight:600;white-space:nowrap;cursor:pointer;box-shadow:none;transition:background .2s ease,color .2s ease,border-color .2s ease,transform .2s ease}
  .wow-cookie-btn:hover,.wow-cookie-btn:focus-visible{background:#f3f7f5;color:#0b3028;border-color:#9eafa9;outline:none;transform:translateY(-1px)}
  .wow-cookie-btn--primary{border-color:#4f9482;background:#4f9482;color:#fff;box-shadow:0 10px 22px rgba(79,148,130,.2)}
  .wow-cookie-btn--primary:hover,.wow-cookie-btn--primary:focus-visible{border-color:#3f7869;background:#3f7869;color:#fff}
  .wow-cookie-btn--soft{background:#edf7f4;border-color:#c4ded5;color:#2f7464}
  .wow-cookie-btn--quiet{border-color:transparent;background:transparent;color:#68736f}
  @media(max-width:640px){.wow-cookie-banner{padding:16px}.wow-cookie-banner__panel{max-height:calc(100dvh - 32px);padding:26px 20px}.wow-cookie-banner__actions.actions,.wow-cookie-banner__advanced-actions.actions{display:grid;grid-template-columns:1fr;gap:8px}.wow-cookie-banner__actions .wow-cookie-btn,.wow-cookie-banner__advanced-actions .wow-cookie-btn{width:100%}}
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
    document.dispatchEvent(new CustomEvent('wow:popup-opened', { detail: { key: 'cookie-banner' } }));
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
