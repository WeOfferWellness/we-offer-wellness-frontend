@if (request()->path() === '/')
<a class="wow-review-float" id="wow-review-float" href="#trust-reviews" data-review-count="{{ (int) ($verified_count ?? $review_count ?? 0) }}" aria-label="{{ number_format((int) ($verified_count ?? $review_count ?? 0)) }} verified practitioner reviews">
  <span class="wow-review-float__star" aria-hidden="true">
    <svg class="wow-review-float__star-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-width="2" d="M11.083 5.104c.35-.8 1.485-.8 1.834 0l1.752 4.022a1 1 0 0 0 .84.597l4.463.342c.9.069 1.255 1.2.556 1.771l-3.33 2.723a1 1 0 0 0-.337 1.016l1.03 4.119c.214.858-.71 1.552-1.474 1.106l-3.913-2.281a1 1 0 0 0-1.008 0L7.583 20.8c-.764.446-1.688-.248-1.474-1.106l1.03-4.119A1 1 0 0 0 6.8 14.56l-3.33-2.723c-.698-.571-.342-1.702.557-1.771l4.462-.342a1 1 0 0 0 .84-.597l1.753-4.022Z"/>
    </svg>
  </span>
  <span class="wow-review-float__count" data-review-count-text>{{ number_format((int) ($verified_count ?? $review_count ?? 0)) }}</span>
  <span class="wow-review-float__label">reviews</span>
</a>
@endif

<button class="wow-newsletter-trigger" id="wow-newsletter-trigger" type="button" aria-haspopup="dialog" aria-controls="wow-newsletter-modal" aria-label="Join the WOW weekly newsletter" hidden>
  <span class="wow-newsletter-trigger-icon" aria-hidden="true">
    <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16v-5.5A3.5 3.5 0 0 0 7.5 7m3.5 9H4v-5.5A3.5 3.5 0 0 1 7.5 7m3.5 9v4M7.5 7H14m0 0V4h2.5M14 7v3m-3.5 6H20v-6a3 3 0 0 0-3-3m-2 9v4m-8-6.5h1"/></svg>
  </span>
  <span class="wow-newsletter-trigger-copy"><strong>Weekly WOW newsletter</strong><span>Festival news, Mindful Times &amp; more</span></span>
</button>

<div class="wow-newsletter-modal" id="wow-newsletter-modal" role="dialog" aria-modal="true" aria-labelledby="wow-newsletter-title" hidden>
  <div class="wow-newsletter-backdrop" data-wow-newsletter-close></div>
  <section class="wow-newsletter-card">
    <div class="wow-newsletter-layout">
      <div class="wow-newsletter-media">
        <img src="https://studio.weofferwellness.co.uk/storage/uploads/images/47423cd9-76ef-451a-a6d5-1b127d543d5f.jpg" alt="A calm wellness scene with an envelope, herbal tea, books, plants and a relaxed woman">
        <div class="wow-newsletter-media-badge"><span>Once a week</span><strong>Your weekly dose of wellness, culture and what's happening at WOW.</strong></div>
      </div>
      <div class="wow-newsletter-content" id="wow-newsletter-content">
        <div class="wow-newsletter-accent"></div>
        <button class="wow-newsletter-close" type="button" aria-label="Close newsletter" data-wow-newsletter-close><svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>
        <div class="wow-newsletter-default"><div class="wow-newsletter-main">
          <p class="wow-newsletter-eyebrow">The WOW weekly newsletter</p>
          <h2 class="wow-newsletter-title" id="wow-newsletter-title">The best of WOW, delivered once a week.</h2>
          <p class="wow-newsletter-subtitle">One useful email bringing together what's happening across We Offer Wellness, OURVIBE Festival and Mindful Times.</p>
          <div class="wow-newsletter-features" aria-label="What you'll receive">
            <div class="wow-newsletter-feature"><div class="wow-newsletter-feature-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M4 6.5h16v11H4v-11Z" stroke="currentColor" stroke-width="1.8"/><path d="m4.7 7.2 7.3 5.6 7.3-5.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div><strong>Weekly newsletter</strong><span>Curated wellness discoveries, events, therapies and things worth knowing.</span></div></div>
            <div class="wow-newsletter-feature"><div class="wow-newsletter-feature-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M8 21v-8a4 4 0 0 1 8 0v8M5 21h14M7 5h10l1 5H6l1-5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 5V3m6 2V3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div><div><strong>Latest from OURVIBE Festival</strong><span>Festival announcements, programme news, tickets and what's coming next.</span></div></div>
            <div class="wow-newsletter-feature"><div class="wow-newsletter-feature-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M5 4h14v16H5V4Z" stroke="currentColor" stroke-width="1.7"/><path d="M8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div><div><strong>Latest Mindful Times articles</strong><span>Fresh articles, interviews and practical wellbeing reads from Mindful Times.</span></div></div>
          </div>
          <form class="wow-newsletter-form" id="wow-newsletter-form" data-subscriber-form="newsletter-modal" data-subscriber-source="Popup update" novalidate>
            <div class="wow-newsletter-input-wrap"><svg class="wow-newsletter-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6.5h16v11H4v-11Z" stroke="currentColor" stroke-width="1.7"/><path d="m4.7 7.2 7.3 5.6 7.3-5.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg><input class="wow-newsletter-input" id="wow-newsletter-email" name="email" type="email" inputmode="email" autocomplete="email" placeholder="Enter your email address" aria-label="Email address" required></div>
            <button class="wow-newsletter-submit" id="wow-newsletter-submit" type="submit">Subscribe <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
          </form>
          <div class="wow-newsletter-message" id="wow-newsletter-message" role="status" aria-live="polite"></div>
          <p class="wow-newsletter-meta"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2M6 10h12v10H6V10Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg><span>No spam. Unsubscribe whenever you like. See our <a href="/privacy-policy">Privacy Policy</a>.</span></p>
        </div></div>
        <div class="wow-newsletter-success" aria-live="polite"><div class="wow-newsletter-success-inner"><div class="wow-newsletter-success-icon" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="m5 12.5 4.2 4.2L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div><h3>You're on the list.</h3><p>Welcome to the WOW weekly newsletter. We'll keep you up to date with WOW, OURVIBE Festival and the latest from Mindful Times.</p><button type="button" data-wow-newsletter-close>Continue browsing</button></div></div>
      </div>
    </div>
  </section>
</div>
