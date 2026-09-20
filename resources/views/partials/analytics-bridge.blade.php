<script>
  (function () {
    var pageViewSent = false;
    var analyticsGranted = false;

    function currentPageParams() {
      return {
        page_location: window.location.pathname + window.location.search + window.location.hash,
        page_title: document.title || '',
      };
    }

    function trackPageView() {
      if (pageViewSent || !analyticsGranted || typeof window.gtag !== 'function') return;
      pageViewSent = true;
      window.gtag('event', 'page_view', currentPageParams());
    }

    function syncConsent(event) {
      try {
        var preferences = event && event.detail;
        if (!preferences) preferences = JSON.parse(localStorage.getItem('wow_cookie_preferences') || '{}');
        analyticsGranted = preferences.analytics === true;
        window.gtag?.('consent', 'update', {
          analytics_storage: analyticsGranted ? 'granted' : 'denied',
          ad_storage: analyticsGranted ? 'granted' : 'denied',
          ad_user_data: analyticsGranted ? 'granted' : 'denied',
          ad_personalization: analyticsGranted ? 'granted' : 'denied',
        });
        if (analyticsGranted) trackPageView();
      } catch (_) {}
    }

    syncConsent();
    document.addEventListener('wow:cookie-preferences', syncConsent);
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', trackPageView, { once: true });
    } else {
      trackPageView();
    }
  })();
</script>
