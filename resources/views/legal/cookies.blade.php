@extends('layouts.app')
@section('title', 'Cookie preferences & policy | We Offer Wellness™')
@section('meta_description', 'Read how We Offer Wellness uses cookies plus manage analytics, personalisation, and marketing preferences at any time.')
@section('content')
<section class="section">
  <div class="container-page cookie-hero">
    <p class="kicker">Transparency first</p>
    <h1>Cookie preferences & policy</h1>
    <p>We use cookies and similar storage to keep your account secure, understand performance and share relevant rituals. You decide what’s on.</p>
    <div class="cookie-hero__actions">
      <button type="button" class="btn-wow btn-wow--primary" data-cookie-preferences-trigger>Adjust cookie preferences</button>
      <a href="/privacy" class="btn-wow btn-wow--soft">Read privacy policy</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container-page cookie-grid">
    <article class="cookie-card">
      <h2>How we categorise cookies</h2>
      <p>We bundle cookies into the groups used in the preference centre so it’s easy to understand the impact of each choice. Retention is 12 months unless noted.</p>
      <ul class="cookie-list">
        <li>
          <h3>Essential (always enabled)</h3>
          <p>Authentication, load balancing, fraud prevention, and booking cart data. Without these, the site won’t work or stay secure.</p>
        </li>
        <li>
          <h3>Analytics</h3>
          <p>Event metrics (e.g. Matomo or privacy-friendly GA4) that help us measure which rituals perform best and surface bugs. We use aggregated data only.</p>
        </li>
        <li>
          <h3>Personalisation</h3>
          <p>Remembers your preferred city, travel radius, and saved goals to tailor hero modules, upsells, and email content.</p>
        </li>
        <li>
          <h3>Marketing</h3>
          <p>Helps launch targeted campaigns across Meta, Google, and email so we only send relevant drops. Disabling keeps communications generic.</p>
        </li>
      </ul>
    </article>
    <article class="cookie-card">
      <h2>Your controls</h2>
      <ol class="cookie-steps">
        <li>Use the “Adjust cookie preferences” button above (or in the footer) to open our on-site controller.</li>
        <li>Toggle analytics, personalisation, and marketing individually, then save. Your decision is stored locally and in a lightweight cookie so we remember it.</li>
        <li>You can also clear cookies directly in your browser for a full reset. We’ll prompt again next visit.</li>
      </ol>
      <p>Need more help? Email <a href="mailto:support@weofferwellness.co.uk">support@weofferwellness.co.uk</a> and we’ll walk you through the options.</p>
    </article>
  </div>
</section>

<section class="section">
  <div class="container-page">
    <h2>Detailed breakdown</h2>
    <div class="cookie-table">
      <div class="cookie-table__row">
        <div>
          <strong>wow_session</strong>
          <p>Stores login state, CSRF tokens, and checkout information.</p>
        </div>
        <div>
          <span>Essential</span>
          <small>Session</small>
        </div>
      </div>
      <div class="cookie-table__row">
        <div>
          <strong>wow_cookie_preferences</strong>
          <p>Remembers the settings you choose in the cookie banner.</p>
        </div>
        <div>
          <span>Essential</span>
          <small>12 months</small>
        </div>
      </div>
      <div class="cookie-table__row">
        <div>
          <strong>Matomo / GA4 events</strong>
          <p>Anonymous identifiers so we can measure site performance. Disabled when analytics is off.</p>
        </div>
        <div>
          <span>Analytics</span>
          <small>13 months</small>
        </div>
      </div>
      <div class="cookie-table__row">
        <div>
          <strong>Personalisation cache</strong>
          <p>Saves your selected travel radius, nearby city, and favourite rituals.</p>
        </div>
        <div>
          <span>Personalisation</span>
          <small>6 months</small>
        </div>
      </div>
      <div class="cookie-table__row">
        <div>
          <strong>Marketing pixels</strong>
          <p>Meta or Google tags that help us avoid spamming you with irrelevant ads. Disabled when marketing is off.</p>
        </div>
        <div>
          <span>Marketing</span>
          <small>90 days</small>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container-page">
    <h2>Browser-level controls</h2>
    <p>You can also block or delete cookies via your browser. Here are quick links:</p>
    <ul class="cookie-links">
      <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Chrome</a></li>
      <li><a href="https://support.mozilla.org/en-US/kb/cookies-information-websites-store-on-your-computer" target="_blank" rel="noopener">Firefox</a></li>
      <li><a href="https://support.apple.com/en-gb/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Safari</a></li>
      <li><a href="https://support.microsoft.com/en-us/topic/view-cookies-in-microsoft-edge-browser-5fc90c4f-3f4f-2a9d-c0b4-5d5c65174803" target="_blank" rel="noopener">Edge</a></li>
    </ul>
  </div>
</section>

<style>
  .cookie-hero{ max-width:820px; }
  .cookie-hero__actions{ display:flex; flex-wrap:wrap; gap:12px; margin-top:18px; }
  .cookie-grid{ display:grid; gap:24px; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); }
  .cookie-card{ border:1px solid rgba(15,23,42,.08); border-radius:3px; padding:24px; background:#fff; }
  .cookie-list{ list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:16px; }
  .cookie-list h3{ margin:0 0 4px; }
  .cookie-list p{ margin:0; color:#475569; }
  .cookie-steps{ margin:0 0 16px 1.2rem; }
  .cookie-steps li{ margin-bottom:10px; }
  .cookie-table{ border:1px solid rgba(15,23,42,.08); border-radius:3px; overflow:hidden; }
  .cookie-table__row{ display:flex; justify-content:space-between; gap:16px; padding:16px; border-bottom:1px solid rgba(15,23,42,.08); }
  .cookie-table__row:last-child{ border-bottom:none; }
  .cookie-table__row strong{ display:block; }
  .cookie-table__row span{ font-weight:600; }
  .cookie-links{ list-style:none; padding:0; display:flex; gap:16px; flex-wrap:wrap; }
</style>
@endsection
