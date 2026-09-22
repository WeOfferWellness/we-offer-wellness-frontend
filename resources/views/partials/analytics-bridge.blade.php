<script>
  (function () {
    function syncConsent(event) {
      try {
        var preferences = event && event.detail;
        if (!preferences) preferences = JSON.parse(localStorage.getItem('wow_cookie_preferences') || '{}');
        window.gtag?.('consent', 'update', {
          analytics_storage: preferences.analytics === true ? 'granted' : 'denied',
          ad_storage: preferences.marketing === true ? 'granted' : 'denied',
          ad_user_data: preferences.marketing === true ? 'granted' : 'denied',
          ad_personalization: preferences.marketing === true ? 'granted' : 'denied',
          personalization_storage: preferences.personalization === true ? 'granted' : 'denied',
        });
      } catch (_) {}
    }

    syncConsent();
    document.addEventListener('wow:cookie-preferences', syncConsent);
  })();
</script>
