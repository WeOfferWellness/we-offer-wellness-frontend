@php
    $sitePageSections = is_array($homePageSections ?? null)
        ? $homePageSections
        : (array) config('site-pages.pages.home.default_layout', []);
    $sitePageComponents = (array) config('site-pages.components', []);
@endphp

@once
    @push('styles')
        <style>
            .wow-site-page-layout {
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: hidden;
                overflow-x: clip;
            }

            .wow-site-page-section {
                box-sizing: border-box;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                order: var(--wow-desktop-order, 0);
            }

            .wow-site-page-section[data-show-desktop="0"] {
                display: none;
            }

            @media (max-width: 767.98px) {
                .wow-site-page-section {
                    display: block;
                    order: var(--wow-mobile-order, var(--wow-desktop-order, 0));
                }

                .wow-site-page-section[data-show-mobile="0"] {
                    display: none;
                }
            }
        </style>
    @endpush
@endonce

<div class="wow-site-page-layout" data-site-page-layout="home">
    @foreach($sitePageSections as $sitePageIndex => $sitePageSection)
        @php
            $componentKey = (string) data_get($sitePageSection, 'component', '');
            $componentDefinition = (array) ($sitePageComponents[$componentKey] ?? []);
            $componentView = (string) ($componentDefinition['view'] ?? '');
            $componentCondition = (string) ($componentDefinition['condition'] ?? '');
            $componentWrapper = (string) ($componentDefinition['wrapper'] ?? '');
            $sectionConfig = (array) data_get($sitePageSection, 'config', []);
            $canRender = $componentView !== '' && view()->exists($componentView);

            if ($canRender && $componentCondition !== '') {
                $canRender = (bool) ($$componentCondition ?? false);
            }

            $desktopOrder = max(0, (int) data_get($sitePageSection, 'desktop_order', $sitePageIndex));
            $mobileOrder = max(0, (int) data_get($sitePageSection, 'mobile_order', $sitePageIndex));
            $showDesktop = (bool) data_get($sitePageSection, 'show_desktop', true);
            $showMobile = (bool) data_get($sitePageSection, 'show_mobile', true);
        @endphp

        @if($canRender)
            <div
                class="wow-site-page-section"
                data-page-component="{{ $componentKey }}"
                data-show-desktop="{{ $showDesktop ? '1' : '0' }}"
                data-show-mobile="{{ $showMobile ? '1' : '0' }}"
                style="--wow-desktop-order:{{ $desktopOrder }};--wow-mobile-order:{{ $mobileOrder }}"
            >
                @if($componentWrapper === 'container')
                    <div class="container">
                        @include($componentView, [
                            'section' => $sitePageSection,
                            'sectionConfig' => $sectionConfig,
                        ])
                    </div>
                @else
                    @include($componentView, [
                        'section' => $sitePageSection,
                        'sectionConfig' => $sectionConfig,
                    ])
                @endif
            </div>
        @endif
    @endforeach
</div>
