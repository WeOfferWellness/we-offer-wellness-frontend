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
  gtag('consent', 'default', @json(array_merge(config('analytics.consent_default'), [
    'personalization_storage' => 'denied',
  ])));
</script>
<script async data-cfasync="false" src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
<script data-cfasync="false">
  gtag('js', new Date());
  gtag('config', @json($gaId), {
    send_page_view: true
  });
</script>
@endif
