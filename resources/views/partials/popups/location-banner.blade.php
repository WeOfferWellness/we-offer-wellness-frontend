<div id="wow-location-banner" class="wow-location-banner" data-wow-popup="location-banner" hidden aria-hidden="true">
    <div class="wow-location-banner__panel" role="dialog" aria-modal="true" aria-labelledby="wowLocationTitle">
        <div class="wow-location-banner__simple">
            <p class="wow-location-banner__eyebrow">Your location</p>
            <h2 id="wowLocationTitle">Help us find locations near you</h2>
            <p>Share your location and we’ll show therapies, classes and events close to you first. We’ll remember your choice for future visits.</p>
            <div class="wow-location-banner__actions actions">
                <button type="button" class="wow-location-btn wow-location-btn--primary" data-wow-location-allow><span>Allow and remember</span></button>
                <button type="button" class="wow-location-btn" data-wow-location-skip>Not now</button>
            </div>
            <div class="wow-location-banner__error" data-wow-location-error hidden></div>
        </div>
    </div>
</div>
<style>
.wow-location-banner{position:fixed;inset:0;z-index:1200;display:grid;place-items:center;width:100%;padding:20px;background:rgba(11,48,40,.28);backdrop-filter:blur(4px);font-family:'Instrument Sans',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
.wow-location-banner[hidden]{display:none!important}.wow-location-banner__panel{width:min(520px,100%);background:#fff;color:#17201d;border-radius:8px;border:1px solid #dce4e0;box-shadow:0 28px 90px rgba(11,48,40,.22);padding:32px}.wow-location-banner__eyebrow{text-transform:uppercase;letter-spacing:.24em;font-size:11px;color:#4f9482;font-weight:700;margin:0 0 8px}.wow-location-banner__simple h2{margin:0 0 8px;color:#0b3028;font-family:'Playfair Display',serif;font-size:clamp(30px,5vw,42px);font-weight:500;line-height:1;letter-spacing:-.04em}.wow-location-banner__simple p{margin:0 0 16px;font-size:14px;line-height:1.6;color:#68736f}.wow-location-banner__actions.actions{display:flex;gap:10px;flex-wrap:wrap}.wow-location-banner__actions .wow-location-btn{flex:1 1 auto;min-width:110px}.wow-location-btn{height:42px;min-height:42px;border-radius:999px;font-size:13px;font-weight:600;border:1px solid #cfd9d5;background:#fff;color:#17201d;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:none;padding:0 18px;transition:background .2s ease,color .2s ease,border-color .2s ease}.wow-location-btn:hover,.wow-location-btn:focus-visible{background:#f3f7f5;color:#0b3028;border-color:#9eafa9;outline:none}.wow-location-btn--primary{background:#4f9482;color:#fff;border-color:#4f9482;box-shadow:0 10px 22px rgba(79,148,130,.2)}.wow-location-btn:disabled{opacity:.7;cursor:not-allowed}.wow-location-banner__error{margin-top:12px;color:#b91c1c;font-size:12px}@media(max-width:640px){.wow-location-banner__panel{padding:26px 20px}.wow-location-banner__actions .wow-location-btn{width:100%}}
</style>
