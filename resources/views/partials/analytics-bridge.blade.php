<script data-cfasync="false">
  (function () {
    function syncConsent(event) {
      try {
        var preferences = event && event.detail;
        if (!preferences) {
          preferences = JSON.parse(localStorage.getItem('wow_cookie_preferences') || '{}');
        }

        if (typeof window.WOWSyncGoogleConsent === 'function') {
          window.WOWSyncGoogleConsent(preferences);
        }
      } catch (_) {}
    }

    // Handles pages where analytics is deliberately disabled as well as
    // preference changes made after the document has loaded.
    syncConsent();
    document.addEventListener('wow:cookie-preferences', syncConsent);
  })();
</script>
