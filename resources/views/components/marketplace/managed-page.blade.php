@props([])

@php
    $route = request()->route();
    $routeUri = $route?->uri();
    $routeName = $route?->getName();
    $routeAction = ltrim((string) $route?->getActionName(), chr(92));

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

        $managedSections = is_array($managedPayload['managed_sections'] ?? null)
            ? $managedPayload['managed_sections']
            : (is_array($managedPayload['sections'] ?? null) ? $managedPayload['sections'] : []);
    }

    $slotHtml = (string) $slot;
    $hasManagedFragments = str_contains($slotHtml, 'data-page-section-id="');

    if (! $isHomeRoute && $managedSections !== []) {
        foreach ($managedSections as $managedIndex => $managedSection) {
            $sectionId = trim((string) data_get($managedSection, 'id', ''));
            if ($sectionId === '') {
                continue;
            }

            $safeId = e($sectionId);
            $needle = 'data-page-section-id="'.$safeId.'"';

            if (! str_contains($slotHtml, $needle)) {
                continue;
            }

            $desktopOrder = max(0, (int) data_get($managedSection, 'desktop_order', $managedIndex));
            $mobileOrder = max(0, (int) data_get($managedSection, 'mobile_order', $managedIndex));
            $active = (bool) data_get($managedSection, 'active', data_get($managedSection, 'enabled', true));
            $showDesktop = $active && (bool) data_get($managedSection, 'show_desktop', true);
            $showMobile = $active && (bool) data_get($managedSection, 'show_mobile', true);

            $replacement = $needle
                .' data-managed-active="'.($active ? '1' : '0').'"'
                .' data-managed-show-desktop="'.($showDesktop ? '1' : '0').'"'
                .' data-managed-show-mobile="'.($showMobile ? '1' : '0').'"'
                .' style="--wow-managed-desktop-order:'.$desktopOrder.';--wow-managed-mobile-order:'.$mobileOrder.'"';

            $slotHtml = str_replace($needle, $replacement, $slotHtml);
        }
    }

    $dynamicSections = [];

    if (! $isHomeRoute && $managedSections !== []) {
        foreach ($managedSections as $managedIndex => $managedSection) {
            $componentKey = (string) data_get($managedSection, 'component', '');
            $sectionId = trim((string) data_get($managedSection, 'id', ''));

            if (
                $componentKey === ''
                || $sectionId === ''
                || ! (bool) data_get($managedSection, 'active', data_get($managedSection, 'enabled', true))
            ) {
                continue;
            }

            if (str_contains($slotHtml, 'data-page-section-id="'.e($sectionId).'"')) {
                continue;
            }

            if (in_array($componentKey, ['legacy_content', 'page_content'], true)) {
                continue;
            }

            $definition = (array) ($managedComponents[$componentKey] ?? []);
            $componentView = (string) ($definition['view'] ?? '');

            if ($componentView === '' || ! view()->exists($componentView)) {
                continue;
            }

            $dynamicSections[] = [
                'index' => $managedIndex,
                'section' => $managedSection,
                'view' => $componentView,
            ];
        }
    }

    $singlePageContentSection = null;

    if (! $isHomeRoute && count($managedSections) === 1) {
        $candidate = $managedSections[0] ?? null;

        if (is_array($candidate) && in_array((string) data_get($candidate, 'component', ''), ['legacy_content', 'page_content'], true)) {
            $singlePageContentSection = $candidate;
        }
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

                .wow-managed-page-fragment,
                .wow-managed-page-dynamic-section {
                    box-sizing: border-box;
                    width: 100%;
                    max-width: 100%;
                    min-width: 0;
                    order: var(--wow-managed-desktop-order, 999);
                }

                .wow-managed-page-fragment[data-managed-show-desktop="0"],
                .wow-managed-page-dynamic-section[data-managed-show-desktop="0"] {
                    display: none;
                }

                @media (max-width: 767.98px) {
                    .wow-managed-page-fragment,
                    .wow-managed-page-dynamic-section {
                        display: block;
                        order: var(--wow-managed-mobile-order, var(--wow-managed-desktop-order, 999));
                    }

                    .wow-managed-page-fragment[data-managed-show-mobile="0"],
                    .wow-managed-page-dynamic-section[data-managed-show-mobile="0"] {
                        display: none;
                    }
                }
            </style>
        @endpush
    @endonce

    <div class="wow-managed-page-layout" data-managed-page-layout="{{ $routeName ?: $identityUri }}">
        @if($singlePageContentSection)
            @php
                $desktopOrder = max(0, (int) data_get($singlePageContentSection, 'desktop_order', 0));
                $mobileOrder = max(0, (int) data_get($singlePageContentSection, 'mobile_order', 0));
                $active = (bool) data_get($singlePageContentSection, 'active', data_get($singlePageContentSection, 'enabled', true));
                $showDesktop = $active && (bool) data_get($singlePageContentSection, 'show_desktop', true);
                $showMobile = $active && (bool) data_get($singlePageContentSection, 'show_mobile', true);
            @endphp
            <div
                class="wow-managed-page-fragment"
                data-page-section-id="{{ data_get($singlePageContentSection, 'id', 'page-content') }}"
                data-page-component="{{ data_get($singlePageContentSection, 'component', 'page_content') }}"
                data-managed-active="{{ $active ? '1' : '0' }}"
                data-managed-show-desktop="{{ $showDesktop ? '1' : '0' }}"
                data-managed-show-mobile="{{ $showMobile ? '1' : '0' }}"
                style="--wow-managed-desktop-order:{{ $desktopOrder }};--wow-managed-mobile-order:{{ $mobileOrder }}"
            >
                {!! $slotHtml !!}
            </div>
        @else
            {!! $slotHtml !!}
        @endif

        @foreach($dynamicSections as $dynamicSection)
            @php
                $managedSection = $dynamicSection['section'];
                $managedIndex = $dynamicSection['index'];
                $sectionConfig = (array) data_get($managedSection, 'config', []);
                $desktopOrder = max(0, (int) data_get($managedSection, 'desktop_order', $managedIndex));
                $mobileOrder = max(0, (int) data_get($managedSection, 'mobile_order', $managedIndex));
                $showDesktop = (bool) data_get($managedSection, 'show_desktop', true);
                $showMobile = (bool) data_get($managedSection, 'show_mobile', true);
            @endphp
            <div
                class="wow-managed-page-dynamic-section"
                data-page-section-id="{{ data_get($managedSection, 'id') }}"
                data-page-component="{{ data_get($managedSection, 'component') }}"
                data-managed-show-desktop="{{ $showDesktop ? '1' : '0' }}"
                data-managed-show-mobile="{{ $showMobile ? '1' : '0' }}"
                style="--wow-managed-desktop-order:{{ $desktopOrder }};--wow-managed-mobile-order:{{ $mobileOrder }}"
            >
                @include($dynamicSection['view'], [
                    'section' => $managedSection,
                    'sectionConfig' => $sectionConfig,
                ])
            </div>
        @endforeach
    </div>

    @once
        @push('scripts')
            <script>
                (() => {
                    const reorderManagedPageFragments = () => {
                        document.querySelectorAll('.wow-managed-page-layout').forEach((layout) => {
                            const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
                            const managed = Array.from(layout.querySelectorAll(
                                '.wow-managed-page-fragment[data-managed-show-desktop], .wow-managed-page-dynamic-section[data-managed-show-desktop]'
                            ));

                            const groups = new Map();
                            managed.forEach((node) => {
                                const parent = node.parentElement;
                                if (!parent) return;
                                const items = groups.get(parent) || [];
                                items.push(node);
                                groups.set(parent, items);
                            });

                            groups.forEach((nodes, parent) => {
                                if (nodes.length < 2) return;

                                const markers = nodes.map((node) => {
                                    const marker = document.createComment('wow-managed-page-slot');
                                    parent.insertBefore(marker, node);
                                    return marker;
                                });

                                const sorted = nodes.slice().sort((left, right) => {
                                    const property = isMobile
                                        ? '--wow-managed-mobile-order'
                                        : '--wow-managed-desktop-order';
                                    const leftOrder = Number.parseInt(left.style.getPropertyValue(property), 10);
                                    const rightOrder = Number.parseInt(right.style.getPropertyValue(property), 10);

                                    return (Number.isFinite(leftOrder) ? leftOrder : 999)
                                        - (Number.isFinite(rightOrder) ? rightOrder : 999);
                                });

                                nodes.forEach((node) => node.remove());

                                markers.forEach((marker, index) => {
                                    const node = sorted[index];
                                    if (node) parent.insertBefore(node, marker);
                                    marker.remove();
                                });
                            });
                        });
                    };

                    const media = window.matchMedia('(max-width: 767.98px)');
                    reorderManagedPageFragments();

                    if (typeof media.addEventListener === 'function') {
                        media.addEventListener('change', reorderManagedPageFragments);
                    } else if (typeof media.addListener === 'function') {
                        media.addListener(reorderManagedPageFragments);
                    }
                })();
            </script>
        @endpush
    @endonce
@endif
