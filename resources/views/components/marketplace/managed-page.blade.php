@props([])

@php
    $route = request()->route();
    $routeUri = $route?->uri();
    $routeName = $route?->getName();
    $routeAction = $route?->getActionName();

    $normalizedUri = trim((string) $routeUri, '/');
    $identityUri = $normalizedUri === '' ? '/' : '/'.$normalizedUri;
    $isHomeRoute = $identityUri === '/';

    $managedSections = [];
    $managedComponents = (array) config('site-pages.components', []);

    if (! $isHomeRoute && $route && $routeAction) {
        $routeKey = sha1(
            trim($identityUri).'|'.trim((string) $routeAction).'|'.trim((string) $routeName)
        );

        $managedPayload = app('App\\Services\\BackendPageLayoutClient')
            ->page('route-'.$routeKey, 'wow-marketplace');

        $managedSections = is_array($managedPayload['sections'] ?? null)
            ? $managedPayload['sections']
            : [];
    }
@endphp

@if($isHomeRoute || $managedSections === [])
    {{ $slot }}
@else
    @once
        @push('styles')
            <style>
                .wow-managed-page-layout {
                    box-sizing: border-box;
                    display: flex;
                    flex-direction: column;
                    width: 100%;
                    max-width: 100%;
                    min-width: 0;
                    overflow-x: hidden;
                    overflow-x: clip;
                }

                .wow-managed-page-section {
                    box-sizing: border-box;
                    width: 100%;
                    max-width: 100%;
                    min-width: 0;
                    order: var(--wow-managed-desktop-order, 0);
                }

                .wow-managed-page-section[data-show-desktop="0"] {
                    display: none;
                }

                @media (max-width: 767.98px) {
                    .wow-managed-page-section {
                        display: block;
                        order: var(--wow-managed-mobile-order, var(--wow-managed-desktop-order, 0));
                    }

                    .wow-managed-page-section[data-show-mobile="0"] {
                        display: none;
                    }
                }
            </style>
        @endpush
    @endonce

    <div class="wow-managed-page-layout" data-managed-page-layout="{{ $routeName ?: $identityUri }}">
        @foreach($managedSections as $managedIndex => $managedSection)
            @php
                $componentKey = (string) data_get($managedSection, 'component', '');
                $definition = (array) ($managedComponents[$componentKey] ?? []);
                $componentView = (string) ($definition['view'] ?? '');
                $sectionConfig = (array) data_get($managedSection, 'config', []);
                $desktopOrder = max(0, (int) data_get($managedSection, 'desktop_order', $managedIndex));
                $mobileOrder = max(0, (int) data_get($managedSection, 'mobile_order', $managedIndex));
                $showDesktop = (bool) data_get($managedSection, 'show_desktop', true);
                $showMobile = (bool) data_get($managedSection, 'show_mobile', true);
            @endphp

            @if($componentKey === 'legacy_content')
                <div
                    class="wow-managed-page-section"
                    data-page-component="legacy_content"
                    data-show-desktop="{{ $showDesktop ? '1' : '0' }}"
                    data-show-mobile="{{ $showMobile ? '1' : '0' }}"
                    style="--wow-managed-desktop-order:{{ $desktopOrder }};--wow-managed-mobile-order:{{ $mobileOrder }}"
                >
                    {{ $slot }}
                </div>
            @elseif($componentView !== '' && view()->exists($componentView))
                <div
                    class="wow-managed-page-section"
                    data-page-component="{{ $componentKey }}"
                    data-show-desktop="{{ $showDesktop ? '1' : '0' }}"
                    data-show-mobile="{{ $showMobile ? '1' : '0' }}"
                    style="--wow-managed-desktop-order:{{ $desktopOrder }};--wow-managed-mobile-order:{{ $mobileOrder }}"
                >
                    @include($componentView, [
                        'section' => $managedSection,
                        'sectionConfig' => $sectionConfig,
                    ])
                </div>
            @endif
        @endforeach
    </div>
@endif
