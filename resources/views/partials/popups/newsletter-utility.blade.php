<aside class="wow-utility-rail" id="wow-utility-rail" aria-label="WOW quick links">
  <div class="wow-utility-rail__stack">
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
  </div>
</aside>
