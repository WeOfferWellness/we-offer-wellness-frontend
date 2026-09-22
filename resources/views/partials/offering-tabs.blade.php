@php
    $tabId = trim((string) ($id ?? 'wow-offering-tabs'));
    $eyebrow = (string) ($eyebrow ?? 'Explore wellness');
    $title = (string) ($title ?? 'Wellness experiences');
    $intro = (string) ($intro ?? 'Browse live offerings from trusted practitioners.');
    $preferredLocation = $preferredLocation ?? null;
    $infinite = (bool) ($infinite ?? false);
    $tabPriceRanges = $tabPriceRanges ?? [];
    $tabs = [
        ['key' => 'local', 'label' => (string) ($localLabel ?? 'Explore wellness'), 'items' => collect($localOfferings ?? [])],
        ['key' => 'new', 'label' => (string) ($newLabel ?? 'New offerings'), 'items' => collect($newOfferings ?? [])],
        ['key' => 'online', 'label' => (string) ($onlineLabel ?? 'Also available online'), 'items' => collect($onlineOfferings ?? [])],
    ];
@endphp

<section class="wow-offering-tabs" id="{{ $tabId }}" data-wow-offering-tabs>
    <div class="container">
        <header class="wow-offering-tabs__header">
            <div>
                <p class="wow-offering-tabs__eyebrow">{{ $eyebrow }}</p>
                <h2 class="wow-offering-tabs__title">{{ $title }}</h2>
                <p class="wow-offering-tabs__intro">{{ $intro }}</p>
            </div>
        </header>

        <div class="wow-tabbar">
            <div class="wow-tabbar__scroll" role="tablist" aria-label="{{ $title }}">
                @foreach($tabs as $index => $tab)
                    <button class="wow-tab{{ $index === 0 ? ' is-active' : '' }}" type="button" role="tab"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                        aria-controls="{{ $tabId }}-panel-{{ $tab['key'] }}"
                        id="{{ $tabId }}-tab-{{ $tab['key'] }}" data-tab="{{ $tab['key'] }}">
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
            <div class="wow-tabbar__controls">
                <button class="wow-carousel-btn wow-carousel-btn--prev" type="button" aria-label="Previous offerings">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </button>
                <button class="wow-carousel-btn wow-carousel-btn--next" type="button" aria-label="Next offerings">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </button>
            </div>
        </div>

        @foreach($tabs as $index => $tab)
            <div class="wow-tabpanel{{ $index === 0 ? ' is-active' : '' }}" id="{{ $tabId }}-panel-{{ $tab['key'] }}" role="tabpanel"
                aria-labelledby="{{ $tabId }}-tab-{{ $tab['key'] }}" data-panel="{{ $tab['key'] }}">
                @php($priceRange = $tabPriceRanges[$tab['key']] ?? [])
                <div class="wow-offering-rail" @if($infinite) data-comfort-rail data-price-min="{{ $priceRange['min'] ?? 0 }}" data-price-max="{{ $priceRange['max'] ?? 999999 }}" @endif>
                    @forelse($tab['items']->take(12) as $product)
                        @include('partials.product_card_v4_1', [
                            'product' => $product,
                            'preferredLocation' => $tab['key'] === 'online' ? null : $preferredLocation,
                        ])
                    @empty
                        <p class="wow-offering-tabs__empty">No offerings are available in this collection yet.</p>
                    @endforelse
                </div>
                @if($infinite)
                    <template data-comfort-ghost>
                        @include('partials.product_card_v4_1_ghost')
                        @include('partials.product_card_v4_1_ghost')
                        @include('partials.product_card_v4_1_ghost')
                        @include('partials.product_card_v4_1_ghost')
                        @include('partials.product_card_v4_1_ghost')
                    </template>
                @endif
            </div>
        @endforeach
    </div>
</section>

<style>
    .wow-offering-tabs { padding: 33px 0 33px; background: #fff; border-top: none; border-bottom: none; }
    .wow-offering-tabs__header { display: flex; align-items: flex-end; justify-content: space-between; gap: 32px; margin-bottom: 26px; }
    .wow-offering-tabs__eyebrow { margin: 0 0 8px; color: var(--wow-green, #4f9482); font-size: 11px; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; }
    .wow-offering-tabs__title { margin: 0; color: var(--wow-green-dark, #0b3028); font: 500 clamp(38px, 4vw, 54px)/1 "Playfair Display", serif; letter-spacing: -.04em; }
    .wow-offering-tabs__intro { max-width: 650px; margin: 12px 0 0; color: var(--wow-muted, #68736f); font-size: 15px; line-height: 1.55; }
    .wow-tabbar { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 28px; border-bottom: 1px solid var(--wow-line, #dce4e0); }
    .wow-tabbar__scroll { display: flex; align-items: flex-end; gap: 30px; min-width: 0; overflow-x: auto; scrollbar-width: none; }
    .wow-tabbar__scroll::-webkit-scrollbar { display: none; }
    .wow-tab { position: relative; flex: 0 0 auto; padding: 0 0 16px; border: 0; background: transparent; color: #3f4945; font: 600 15px/1 "Instrument Sans", sans-serif; cursor: pointer; white-space: nowrap; }
    .wow-tab::after { content: ""; position: absolute; right: 0; bottom: -1px; left: 0; z-index: 2; height: 2px; background: transparent; transform: scaleX(0); transform-origin: left; transition: transform .2s ease, background-color .2s ease; }
    .wow-tab:hover { color: var(--wow-green-dark, #0b3028); }
    .wow-tab.is-active { color: var(--wow-green, #4f9482); font-weight: 700; }
    .wow-tab.is-active::after { background: var(--wow-green, #4f9482); transform: scaleX(1); }
    .wow-tabbar__controls { display: flex; align-items: center; gap: 8px; padding-bottom: 10px; }
    .wow-carousel-btn { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; padding: 0; border: 1px solid var(--wow-line, #dce4e0); border-radius: 50%; background: #fff; color: var(--wow-text, #17201d); cursor: pointer; transition: background-color .18s ease, border-color .18s ease, opacity .18s ease; }
    .wow-carousel-btn:hover { background: var(--wow-soft, #f3f7f5); border-color: #bcc9c4; }
    .wow-carousel-btn:disabled { opacity: .35; cursor: not-allowed; }
    .wow-carousel-btn svg { width: 18px; height: 18px; }
    .wow-tabpanel { display: none; }
    .wow-tabpanel.is-active { display: block; }
    .wow-offering-rail { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(230px, 1fr); gap: 16px; overflow-x: auto; overscroll-behavior-inline: contain; scroll-snap-type: inline mandatory; scroll-behavior: smooth; scrollbar-width: none; padding-bottom: 2px; }
    .wow-offering-rail::-webkit-scrollbar { display: none; }
    .wow-offering-rail .wow410-card { width: 100%; min-width: 0; scroll-snap-align: start; }
    .wow-offering-tabs__empty { grid-column: 1 / -1; margin: 0; color: var(--wow-muted, #68736f); }
    @media (min-width: 1200px) { .wow-offering-rail { grid-auto-columns: calc((100% - 64px) / 5); } }
    @media (min-width: 992px) and (max-width: 1199.98px) { .wow-offering-rail { grid-auto-columns: calc((100% - 48px) / 4); } }
    @media (min-width: 768px) and (max-width: 991.98px) { .wow-offering-rail { grid-auto-columns: calc((100% - 32px) / 3); } }
    @media (max-width: 767.98px) { .wow-offering-rail { grid-auto-columns: calc((100% - 10px) / 2); } }
    @media (min-width: 600px) and (max-width: 767.98px) and (orientation: landscape) { .wow-offering-rail { grid-auto-columns: calc((100% - 32px) / 3); } }
    @media (max-width: 575.98px) { .wow-offering-tabs { padding: 33px 0; } .wow-offering-tabs__header { display: block; margin-bottom: 21px; } .wow-offering-tabs__title { font-size: 38px; } .wow-offering-tabs__intro { font-size: 14px; } .wow-tabbar { margin-bottom: 20px; } .wow-tabbar__scroll { gap: 22px; } .wow-tab { padding-bottom: 13px; font-size: 13px; } .wow-tabbar__controls { display: none; } }
</style>

<script>
document.querySelectorAll('[data-wow-offering-tabs]').forEach(function (component) {
    const tabs = component.querySelectorAll('.wow-tab');
    const panels = component.querySelectorAll('.wow-tabpanel');
    const previous = component.querySelector('.wow-carousel-btn--prev');
    const next = component.querySelector('.wow-carousel-btn--next');

    function activeRail() { return component.querySelector('.wow-tabpanel.is-active .wow-offering-rail'); }
    function updateControls() {
        const rail = activeRail();
        if (!rail) return;
        const maxScroll = rail.scrollWidth - rail.clientWidth;
        previous.disabled = rail.scrollLeft <= 4;
        next.disabled = rail.scrollLeft >= maxScroll - 4;
    }
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (item) {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panels.forEach(function (panel) { panel.classList.toggle('is-active', panel.dataset.panel === tab.dataset.tab); });
            requestAnimationFrame(updateControls);
        });
    });
    previous.addEventListener('click', function () { const rail = activeRail(); if (rail) rail.scrollBy({ left: -rail.clientWidth * .8, behavior: 'smooth' }); });
    next.addEventListener('click', function () { const rail = activeRail(); if (rail) rail.scrollBy({ left: rail.clientWidth * .8, behavior: 'smooth' }); });
    component.addEventListener('scroll', updateControls, true);
    window.addEventListener('resize', updateControls);
    updateControls();
});
</script>
