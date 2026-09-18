<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        @php
          $seoService = app(\App\Services\SeoStructureService::class);
          $appName = config('app.name', 'We Offer Wellness');
          $defaultDesc = 'Holistic therapy, classes, workshops and retreats from trusted practitioners across the UK, online and in person.';
          $defaultOg = asset('images/default-social-preview.jpg');
          $canonical = $seoService->canonicalUrl(request()->getPathInfo());
          $gtmId = config('services.gtm.id');
          $gaId = config('analytics.enabled') ? config('analytics.measurement_id') : null;
          $favicon = config('app.favicon_url', '/favicon.ico');
          $ogTitle = $seoService->shortOgTitle($appName);
          $ogDesc = $seoService->shortOgDescription($defaultDesc);
        @endphp
        <link rel="canonical" href="{{ $canonical }}" />
        <meta name="description" content="{{ $defaultDesc }}" />
        <meta name="keywords" content="We Offer Wellness, WOW, wellness marketplace, therapies, classes, workshops, events, retreats">

        <!-- Open Graph defaults -->
        <meta property="og:type" content="website" />
        <meta property="og:title" content="{{ $ogTitle }}" />
        <meta property="og:description" content="{{ $ogDesc }}" />
        <meta property="og:url" content="{{ $canonical }}" />
        <meta property="og:image" content="{{ $defaultOg }}" />
        <meta property="og:site_name" content="{{ $appName }}" />

        <!-- Twitter Card defaults -->
        <meta name="twitter:site" content="@weofferwellness" />
        <meta name="twitter:creator" content="@weofferwellness" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="{{ $ogTitle }}" />
        <meta name="twitter:description" content="{{ $ogDesc }}" />
        <meta name="twitter:image" content="{{ $defaultOg }}" />

        <!-- Google Tag Manager (optional via env) -->
        @if ($gtmId)
        <script>
          (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
          new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
          j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
          'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
          })(window,document,'script','dataLayer','{{ $gtmId }}');
        </script>
        @endif

        <!-- Google Analytics 4 (optional via env) -->
        @if ($gaId)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments)}
          gtag('consent', 'default', @json(config('analytics.consent_default')));
          gtag('js', new Date());
          gtag('config', '{{ $gaId }}', { 'send_page_view': false });
        </script>
        @endif
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ $favicon }}">
        <link rel="shortcut icon" href="{{ $favicon }}">
        <link rel="apple-touch-icon" href="{{ $favicon }}">

        <!-- Fonts: Instrument Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,opsz,wght@0,14..32,300..900;1,14..32,300..900&display=swap" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">

        <!-- WOW V3 Typography Deck (source of truth) -->
        <link rel="stylesheet" href="/css/wow-typography.css">

        <!-- Scripts -->
        @routes
        {{-- Load the app bundle only; page chunk is dynamically imported by Inertia --}}
        @php $manifest = public_path('build/manifest.json'); @endphp
        @if (file_exists($manifest))
            @vite('resources/js/app.js')
        @else
            <!-- Vite manifest missing; temporarily skip assets to avoid 500. Build assets via `npm run build`. -->
        @endif
        <script data-cfasync="false">
          window.WOW_MAPS_KEY = @json(config('services.mapbox.token'));
          window.WOW_APP_NAME = @json($appName);
        </script>
        
        <!-- Organization JSON-LD -->
        @php
          $siteUrl = url('/');
          $orgLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $siteUrl . '#organization',
            'name' => $appName,
            'url' => $siteUrl,
            'logo' => $favicon,
            'sameAs' => [
              'https://www.instagram.com/weofferwellness',
              'https://www.tiktok.com/@weofferwellness',
              'https://www.linkedin.com/company/weofferwellness',
              'https://www.facebook.com/WeOfferWellness',
            ],
          ];
          $siteLd = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $siteUrl . '#website',
            'url' => $siteUrl,
            'name' => $appName,
            'publisher' => [
              '@id' => $siteUrl . '#organization',
            ],
            'potentialAction' => [
              '@type' => 'SearchAction',
              'target' => $siteUrl . 'search?what={search_term_string}',
              'query-input' => 'required name=search_term_string',
            ],
          ];
        @endphp
        <script type="application/ld+json">{!! json_encode($orgLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
        <script type="application/ld+json">{!! json_encode($siteLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
        @inertiaHead
    </head>
    <body class="antialiased">
        @if ($gtmId)
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        @endif
        @inertia

        <script>
          // Basic analytics bridge for SPA navigations and key commerce events
          (function(){
            function track(name, params){
              try {
                if (window.WOWAnalytics && typeof window.WOWAnalytics.track === 'function') {
                  return window.WOWAnalytics.track(name, params || {});
                }
                if (typeof window.gtag === 'function') {
                  return window.gtag('event', name, params || {});
                }
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ event:name, ...(params || {}) });
              } catch(e){}
            }
            function currentPageParams(){
              try {
                return {
                  page_location: location.pathname + location.search + location.hash,
                  page_title: document.title,
                };
              } catch(_){
                return { page_location: '', page_title: document.title || '' };
              }
            }
            function trackInitialPageView(){
              track('page_view', currentPageParams());
            }
            function persistAttribution(){
              try {
                var params = new URLSearchParams(location.search);
                var keys = ['utm_source','utm_medium','utm_campaign','utm_term','utm_content','gclid','fbclid'];
                var current = JSON.parse(sessionStorage.getItem('wow_attribution') || '{}');
                var first = JSON.parse(localStorage.getItem('wow_first_touch') || '{}');
                keys.forEach(function(key){
                  var value = (params.get(key) || '').trim();
                  if (!value) return;
                  current[key] = value;
                  if (!first[key]) first[key] = value;
                });
                sessionStorage.setItem('wow_attribution', JSON.stringify(current));
                localStorage.setItem('wow_first_touch', JSON.stringify(first));
              } catch (_) {}
            }
            function syncConsent(){
              try {
                var preferences = JSON.parse(localStorage.getItem('wow_cookie_preferences') || '{}');
                var granted = preferences.analytics === true || preferences.performance === true;
                if (typeof window.gtag === 'function') window.gtag('consent', 'update', {
                  analytics_storage: granted ? 'granted' : 'denied',
                  ad_storage: granted ? 'granted' : 'denied',
                  ad_user_data: granted ? 'granted' : 'denied',
                  ad_personalization: granted ? 'granted' : 'denied'
                });
              } catch (_) {}
            }
            persistAttribution();
            syncConsent();
            document.addEventListener('wow:cookie-preferences', syncConsent);
            document.addEventListener('click', function(event){
              try {
                var card = event.target.closest('[data-product-id], .offering-card');
                if (!card || event.target.closest('button')) return;
                var id = card.getAttribute('data-product-id') || card.dataset?.productId;
                if (!id || card.dataset.wowSelectItemSent === '1') return;
                card.dataset.wowSelectItemSent = '1';
                track('select_item', { catalogue_type: card.dataset.sourceVersion === 'store' ? 'product' : (card.dataset.catalogueType || 'offering'), items: [{ item_id: String(id), item_name: card.dataset.productTitle || card.querySelector('h2,h3')?.textContent?.trim() || 'Item', provider_id: card.dataset.providerId || undefined }] });
              } catch (_) {}
            }, true);
            // Inertia page view
            document.addEventListener('inertia:success', function(ev){
              try {
                persistAttribution();
                track('page_view', currentPageParams());
              } catch {}
            });
            // Cart events (custom)
            window.addEventListener('wow:add-to-cart', function(e){
              const detail = e?.detail || {};
              const items = Array.isArray(detail.items) ? detail.items : [detail];
              track('wow_v3_add_to_cart', {
                items,
                currency: detail.currency || 'GBP',
                value: detail.value ?? null,
                item_count: detail.item_count ?? detail.qty ?? null,
                source: detail.source || 'inertia-bridge',
              });
            });
            if (document.readyState === 'loading') {
              document.addEventListener('DOMContentLoaded', trackInitialPageView, { once: true });
            } else {
              trackInitialPageView();
            }
          })();
        </script>
    </body>
</html>
