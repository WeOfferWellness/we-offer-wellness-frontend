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

  function normalizeWowGa4Params(eventName, params) {
    if (!params || typeof params !== 'object' || Array.isArray(params)) return params;

    var safe = Object.assign({}, params);
    [['source', 'interaction_source'], ['medium', 'interaction_medium'], ['campaign', 'interaction_campaign']]
      .forEach(function(pair) {
        var legacyKey = pair[0];
        var safeKey = pair[1];
        if (safe[legacyKey] != null && safe[safeKey] == null) safe[safeKey] = safe[legacyKey];
        delete safe[legacyKey];
      });

    if (
      eventName === 'page_view'
      && typeof safe.page_location === 'string'
      && safe.page_location.charAt(0) === '/'
      && window.location
    ) {
      safe.page_location = window.location.origin + safe.page_location;
    }

    return safe;
  }

  window.gtag = window.gtag || function gtag(){
    if (arguments[0] === 'event' && arguments.length >= 3) {
      arguments[2] = normalizeWowGa4Params(arguments[1], arguments[2]);
    }
    window.dataLayer.push(arguments);
  };
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

  // Returning visitors who already granted consent are restored before the
  // Google tag/config call, so the first page_view joins the correct session.
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
