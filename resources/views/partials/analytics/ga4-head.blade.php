@php
    $isPublicProductionSite = in_array(request()->getHost(), ['www.weofferwellness.co.uk', 'weofferwellness.co.uk'], true);
    $gaId = config('analytics.measurement_id');
    if (! config('analytics.enabled') && ! $isPublicProductionSite) {
        $gaId = null;
    }
    if ($isPublicProductionSite && ! $gaId) {
        $gaId = 'G-MZMQNETBYH';
    }
@endphp
@if ($gaId)
<script data-cfasync="false">
  window.dataLayer = window.dataLayer || [];
  window.gtag = window.gtag || function gtag(){ window.dataLayer.push(arguments); };
  window.WOW_GA4_MEASUREMENT_ID = @json($gaId);

  window.WOWSyncGoogleConsent = window.WOWSyncGoogleConsent || function(preferences) {
    preferences = preferences || {};
    window.gtag('consent', 'update', {
      analytics_storage: preferences.analytics === true ? 'granted' : 'denied',
      ad_storage: preferences.marketing === true ? 'granted' : 'denied',
      ad_user_data: preferences.marketing === true ? 'granted' : 'denied',
      ad_personalization: preferences.marketing === true ? 'granted' : 'denied',
      personalization_storage: preferences.personalization === true ? 'granted' : 'denied'
    });
  };

  // Advanced Consent Mode. New visitors send cookieless measurement pings.
  gtag('consent', 'default', @json(array_merge(config('analytics.consent_default'), [
    'personalization_storage' => 'denied',
  ])));

  // Apply a returning visitor's stored consent before gtag.js/config runs so
  // the first page_view joins the correct cookie/session whenever consent was
  // already granted on an earlier visit.
  try {
    var storedPreferences = JSON.parse(localStorage.getItem('wow_cookie_preferences') || 'null');
    if (storedPreferences) window.WOWSyncGoogleConsent(storedPreferences);
  } catch (_) {}
</script>
<script async data-cfasync="false" src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
<script data-cfasync="false">
  gtag('js', new Date());
  gtag('config', @json($gaId), {
    send_page_view: true
  });
</script>
@endif
