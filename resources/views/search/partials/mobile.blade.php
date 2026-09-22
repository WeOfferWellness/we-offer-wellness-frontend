@php
    $mobileResultsCount = $mobileResultsCount ?? (method_exists($products, 'total') ? $products->total() : $products->count());
@endphp

@once
    <style>
        .wow-sr-v5-mobile {
            --sr-green: #4f9a86;
            --sr-green-dark: #2f7464;
            --sr-green-pale: #e4f2ee;
            --sr-ink: #141a2a;
            --sr-line: #dfe5ea;
            --sr-page: #fbfaf8;
            color: var(--sr-ink);
            padding-bottom: 32px;
        }
        @media (min-width: 1041px) {
            .wow-sr-v5-mobile { display: none !important; }
        }

        .wow-sr-v5-mobile-toolbar {
            position: sticky;
            top: 0;
            z-index: 30;
            min-height: 57px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 0 11px;
            border-top: 1px solid #eceff1;
            border-bottom: 1px solid #eceff1;
            background: rgba(251, 250, 248, .97);
            backdrop-filter: blur(7px);
        }

        .wow-sr-v5-mobile-count { display: grid; gap: 1px; color: #202b3b; font-size: 13px; font-weight: 700; line-height: 1.15; }
        .wow-sr-v5-mobile-count strong { color: #202b3b; }
        .wow-sr-v5-mobile-count span { color: #8c98aa; font-size: 9px; font-weight: 650; }
        .wow-sr-v5-mobile-count b { color: var(--sr-green); }
        .wow-sr-v5-mobile-actions { display: flex; align-items: center; gap: 7px; }
        .wow-sr-v5-mobile[data-show-map="true"] [data-map-toggle] { display: inline-flex; }
        .wow-sr-v5-mobile-tool {
            min-height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 12px;
            border: 1px solid #d6dee5;
            border-radius: 999px;
            background: #fff;
            color: #2d394e;
            font-size: 11px;
            font-weight: 650;
            cursor: pointer;
        }

        .wow-sr-v5-mobile-results { padding: 13px 10px 0; }
        .wow-search-recommendations { margin: 0 0 20px; }
        .wow-search-recommendations__heading { display: flex; align-items: end; justify-content: space-between; gap: 10px; margin: 0 2px 11px; }
        .wow-search-recommendations__kicker { margin: 0 0 2px; color: #7b8598; font-size: 8px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .wow-search-recommendations__heading h2 { margin: 0; color: #111827; font-family: "DM Serif Display", Georgia, serif; font-size: 23px; font-weight: 400; line-height: 1.05; }
        .wow-search-recommendations__heading > span { color: #8c98aa; font-size: 9px; white-space: nowrap; }
        .wow-search-recommendations__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 11px; align-items: start; }
        .wow-search-recommendation { position: relative; min-width: 0; padding-top: 13px; }
        .wow-search-recommendation__label { position: absolute; top: 0; left: 7px; z-index: 5; display: inline-flex; align-items: center; min-height: 22px; max-width: calc(100% - 14px); overflow: hidden; padding: 0 7px; border-radius: 999px; background: #e4f2ee; color: #2f7464; font-size: 7px; font-weight: 800; letter-spacing: .03em; text-overflow: ellipsis; white-space: nowrap; }
        .wow-search-recommendation.is-primary .wow-search-recommendation__label { background: #2f7464; color: #fff; }
        .wow-search-recommendation .wow410-card { width: 100%; min-width: 0; max-width: none; }
        .wow-sr-v5-mobile .wow-search-recommendation:nth-child(n + 3) { display: none; }
        .wow-sr-v5-all-results-title { margin: 18px 2px 10px; color: #53627a; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .wow-sr-v5-mobile-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 11px; align-items: start; }
        .wow-sr-v5-mobile-grid > .col-12 { width: auto; max-width: none; padding: 0; }
        .wow-sr-v5-mobile-grid .result-view-map { display: none; }
        .wow-sr-v5-mobile-grid .result-view-list { display: block; }
        .wow-sr-v5-mobile-grid .wow410-card,
        .wow-sr-v5-mobile-grid .product-v4-1-ghost-card { width: 100%; min-width: 0; max-width: none; }
        .wow-sr-v5-mobile-pagination { margin: 22px 0 0; }
        .wow-sr-v5-mobile-pagination .pagination { justify-content: center; margin-bottom: 0; }

        .wow-sr-v5-mobile-map { display: none; position: relative; min-height: calc(100dvh - 57px); background: #eef0f2; }
        .wow-sr-v5-mobile-map-canvas { width: 100%; min-height: calc(100dvh - 57px); height: calc(100dvh - 57px); }
        .wow-sr-v5-mobile-map-empty { position: absolute; inset: 0; display: grid; place-items: center; padding: 24px; color: #61708a; font-size: 13px; text-align: center; pointer-events: none; }
        .wow-sr-v5-mobile-map.is-ready .wow-sr-v5-mobile-map-empty { display: none; }
        .wow-sr-v5-mobile.is-map-mode .wow-sr-v5-mobile-results { display: none; }
        .wow-sr-v5-mobile.is-map-mode .wow-sr-v5-mobile-map { display: block; }
        .wow-sr-v5-mobile-map-info { max-width: 230px; color: #26334a; font-family: Inter, sans-serif; font-size: 12px; line-height: 1.4; }
        .wow-sr-v5-mobile-map-info strong { display: block; margin-bottom: 4px; color: #141a2a; font-size: 13px; }
        .wow-sr-v5-mobile-map-info a { color: var(--sr-green-dark); font-weight: 700; text-decoration: none; }

        .wow-sr-v5-mobile-modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .wow-sr-v5-mobile-modal[hidden] { display: none; }
        .wow-sr-v5-mobile-backdrop { position: absolute; inset: 0; border: 0; background: rgba(0, 0, 0, .40); backdrop-filter: blur(2px); cursor: pointer; }
        .wow-sr-v5-mobile-sheet {
            position: relative;
            height: 88dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 24px 24px 0 0;
            background: #fff;
            box-shadow: 0 -8px 40px rgba(0, 0, 0, .18);
        }

        .wow-sr-v5-sheet-handle { display: flex; justify-content: center; flex: 0 0 auto; padding: 12px 0 4px; }
        .wow-sr-v5-sheet-handle::before { width: 40px; height: 4px; border-radius: 999px; background: #d3d7de; content: ''; }
        .wow-sr-v5-sheet-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex: 0 0 auto; padding: 13px 17px 15px; border-bottom: 1px solid #e8ebef; }
        .wow-sr-v5-sheet-heading { min-width: 0; display: flex; align-items: center; gap: 11px; }
        .wow-sr-v5-sheet-icon { width: 38px; height: 38px; display: grid; flex: 0 0 38px; place-items: center; border-radius: 11px; background: #edf7f4; color: var(--sr-green-dark); }
        .wow-sr-v5-sheet-kicker { margin: 0 0 2px; color: #98a2b3; font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .wow-sr-v5-sheet-title { margin: 0; color: #101828; font-size: 20px; font-weight: 700; line-height: 1.15; }
        .wow-sr-v5-sheet-close { width: 38px; height: 38px; display: grid; flex: 0 0 38px; place-items: center; border: 0; border-radius: 50%; background: #f2f4f7; color: #455268; cursor: pointer; }
        .wow-sr-v5-sheet-body { flex: 1; overflow-y: auto; padding: 0 17px 100px; }

        .wow-sr-v5-mobile-filter-section { padding: 19px 0 16px; border-bottom: 1px solid #e8ebef; }
        .wow-sr-v5-mobile-filter-section.is-collapsed .wow-sr-v5-mobile-filter-body { display: none; }
        .wow-sr-v5-mobile-filter-section.is-collapsed .wow-sr-v5-mobile-chevron { transform: rotate(180deg); }
        .wow-sr-v5-mobile-filter-head { width: 100%; min-height: 31px; display: flex; align-items: center; justify-content: space-between; padding: 0; border: 0; border-radius: 8px; background: transparent; color: #111827; text-align: left; cursor: pointer; }
        .wow-sr-v5-mobile-filter-name { display: flex; align-items: center; gap: 9px; font-size: 13px; font-weight: 750; }
        .wow-sr-v5-mobile-filter-icon { width: 27px; height: 27px; display: grid; flex: 0 0 27px; place-items: center; border-radius: 7px; background: #f2f6f5; color: #5f746e; }
        .wow-sr-v5-mobile-chevron { width: 27px; height: 27px; display: grid; flex: 0 0 27px; place-items: center; border-radius: 50%; background: #f4f6f8; color: #7e8a9c; transition: transform .2s ease; }
        .wow-sr-v5-mobile-filter-body { display: grid; gap: 5px; margin-top: 13px; }
        .wow-sr-v5-mobile-choice { width: 100%; min-height: 38px; display: flex; align-items: center; gap: 10px; padding: 0 9px; border: 1px solid transparent; border-radius: 9px; background: transparent; color: #344158; font-size: 12px; text-align: left; cursor: pointer; }
        .wow-sr-v5-mobile-choice.is-selected { border-color: #d5e9e3; background: #e5f3ef; color: var(--sr-green-dark); }
        .wow-sr-v5-mobile-choice-box { width: 17px; height: 17px; flex: 0 0 17px; border: 1px solid #cad3dc; border-radius: 5px; background: #fff; }
        .wow-sr-v5-mobile-choice.is-selected .wow-sr-v5-mobile-choice-box { border-color: var(--sr-green); background: var(--sr-green); }
        .wow-sr-v5-mobile-choice-copy { min-width: 0; flex: 1; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .wow-sr-v5-mobile-choice-copy strong { font-size: 12px; font-weight: 650; }
        .wow-sr-v5-mobile-choice-note { color: #9aa5b3; font-size: 9px; font-weight: 600; }
        .wow-sr-v5-mobile-stars { color: #f2b827; font-size: 9px; letter-spacing: 0; }
        .wow-sr-v5-mobile-chip-row { display: flex; flex-wrap: wrap; gap: 7px; }
        .wow-sr-v5-mobile-chip { min-height: 34px; padding: 0 13px; border: 1px solid #d5dde5; border-radius: 999px; background: #fff; color: #34435a; font-size: 12px; cursor: pointer; }
        .wow-sr-v5-mobile-chip.is-selected { border-color: #9cc8bc; background: #edf7f4; color: var(--sr-green-dark); font-weight: 650; }
        .wow-sr-v5-mobile-price-summary { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 0 0 5px; color: #96a1af; font-size: 10px; }
        .wow-sr-v5-mobile-price-summary strong { color: #2e3a4d; font-size: 10px; }
        .wow-sr-v5-mobile-range { width: 100%; height: 24px; margin: 0; padding: 0; appearance: none; background: transparent; cursor: pointer; }
        .wow-sr-v5-mobile-range::-webkit-slider-runnable-track { height: 5px; border-radius: 999px; background: linear-gradient(90deg, var(--sr-green) 0 var(--range-progress, 100%), #e0e6ea var(--range-progress, 100%) 100%); }
        .wow-sr-v5-mobile-range::-moz-range-track { height: 5px; border-radius: 999px; background: #e0e6ea; }
        .wow-sr-v5-mobile-range::-moz-range-progress { height: 5px; border-radius: 999px; background: var(--sr-green); }
        .wow-sr-v5-mobile-range::-webkit-slider-thumb { width: 20px; height: 20px; margin-top: -7.5px; appearance: none; border: 2px solid var(--sr-green); border-radius: 50%; background: #fff; box-shadow: 0 2px 6px rgba(16,24,40,.16); }
        .wow-sr-v5-mobile-range::-moz-range-thumb { width: 18px; height: 18px; border: 2px solid var(--sr-green); border-radius: 50%; background: #fff; }
        .wow-sr-v5-sheet-footer { position: absolute; right: 0; bottom: 0; left: 0; display: grid; grid-template-columns: 151px minmax(0, 1fr); gap: 10px; padding: 10px 17px 16px; border-top: 1px solid #e7ebef; background: #fff; box-shadow: 0 -4px 16px rgba(16,24,40,.03); }
        .wow-sr-v5-sheet-footer button { min-height: 46px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; }
        .wow-sr-v5-clear { border: 1px solid #d4dce4; background: #f8fafb; color: #6b778b; }
        .wow-sr-v5-apply { border: 0; background: var(--sr-green); color: #fff; }
        body.wow-sr-v5-no-scroll { overflow: hidden; }

        @media (max-width: 359px) {
            .wow-sr-v5-mobile-grid, .wow-search-recommendations__grid { grid-template-columns: minmax(0, 1fr); }
            .wow-sr-v5-mobile-tool { padding: 0 9px; }
        }

        /* v4.10 compact landscape contract: three cards per row on tablets
           and landscape mobile widths, including the mobile search renderer
           which remains active below the desktop search breakpoint. */
        @media (min-width: 700px) and (max-width: 1040px) {
            .wow-sr-v5-mobile .wow-sr-v5-mobile-grid,
            .wow-sr-v5-mobile .wow-search-recommendations__grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 16px;
            }

            .wow-sr-v5-mobile-grid .wow410-card,
            .wow-search-recommendation .wow410-card {
                height: 420px;
                grid-template-rows: 138px minmax(0, 1fr) auto;
                border-radius: 12px;
            }
        }

        /* Tablet orientation contract: three columns in portrait and four
           columns when landscape width gives the cards enough room. */
        @media (min-width: 900px) and (max-width: 1040px) and (orientation: landscape) {
            .wow-sr-v5-mobile .wow-sr-v5-mobile-grid,
            .wow-sr-v5-mobile .wow-search-recommendations__grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                gap: 14px;
            }
        }
    </style>
@endonce

<section class="wow-sr-v5-mobile" id="wowMobileSearch" data-search-url="{{ $searchUrl ?? url('/search') }}" data-full-navigation="{{ !empty($mobileFullNavigation) ? 'true' : 'false' }}" data-show-map="{{ !empty($mobileShowMap) ? 'true' : 'false' }}" data-initial-map='@json($searchMapData ?? [])'>
    <div class="wow-sr-v5-mobile-toolbar">
        <div class="wow-sr-v5-mobile-count"><strong>Recommended for you</strong><span><b data-result-count>{{ $mobileResultsCount }}</b> matching offerings</span></div>
        <div class="wow-sr-v5-mobile-actions">
            <button class="wow-sr-v5-mobile-tool" type="button" data-open-filters>
                <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M7 12h10M10 17h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                Filters
            </button>
            @if(!empty($mobileShowMap))
            <button class="wow-sr-v5-mobile-tool" type="button" data-map-toggle aria-pressed="false">
                <svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="m3 6 5-2 8 3 5-2v13l-5 2-8-3-5 2V6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 4v13M16 7v13" stroke="currentColor" stroke-width="1.6"/></svg>
                <span data-map-label>Map</span>
            </button>
            @endif
        </div>
    </div>

    @if(empty($filterOnly))
    <section class="wow-sr-v5-mobile-map" aria-label="Map view" data-map-panel>
        <div class="wow-sr-v5-mobile-map-canvas" data-map-canvas></div>
        <div class="wow-sr-v5-mobile-map-empty" data-map-empty>Choose Map to see nearby wellbeing offerings.</div>
    </section>

    <main class="wow-sr-v5-mobile-results" aria-label="Search results">
        <div id="mobileSearchRecommendations">
            {!! $searchRecommendationsHtml ?? '' !!}
        </div>
        <h2 class="wow-sr-v5-all-results-title">All results</h2>
        <div class="wow-sr-v5-mobile-grid" id="mobileSearchResultsGrid">
            @include('search.partials.results_cards', ['searchAsyncBoot' => (bool) ($searchAsyncBoot ?? false)])
        </div>
        <div class="wow-sr-v5-mobile-pagination" id="mobileSearchResultsPagination">
            @if ($products instanceof \Illuminate\Pagination\Paginator || $products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $products->withQueryString()->onEachSide(1)->links('pagination::bootstrap-4') }}
            @endif
        </div>
    </main>
    @endif

    <div class="wow-sr-v5-mobile-modal" data-filter-modal hidden>
        <button class="wow-sr-v5-mobile-backdrop" type="button" data-close-filters aria-label="Close filters"></button>
        <section class="wow-sr-v5-mobile-sheet" role="dialog" aria-modal="true" aria-labelledby="wow-mobile-filter-title">
            <div class="wow-sr-v5-sheet-handle" aria-hidden="true"></div>
            <header class="wow-sr-v5-sheet-header">
                <div class="wow-sr-v5-sheet-heading">
                    <span class="wow-sr-v5-sheet-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 7h10M18 7h2M4 12h3M11 12h9M4 17h7M15 17h5" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/><circle cx="16" cy="7" r="2" stroke="currentColor" stroke-width="1.6"/><circle cx="9" cy="12" r="2" stroke="currentColor" stroke-width="1.6"/><circle cx="13" cy="17" r="2" stroke="currentColor" stroke-width="1.6"/></svg></span>
                    <div><p class="wow-sr-v5-sheet-kicker">Refine results</p><h2 class="wow-sr-v5-sheet-title" id="wow-mobile-filter-title">Filters &amp; Sort</h2></div>
                </div>
                <button class="wow-sr-v5-sheet-close" type="button" data-close-filters aria-label="Close filters"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>
            </header>

            <div class="wow-sr-v5-sheet-body">
                <section class="wow-sr-v5-mobile-filter-section">
                    <button class="wow-sr-v5-mobile-filter-head" type="button" data-mobile-section-toggle aria-expanded="true"><span class="wow-sr-v5-mobile-filter-name"><span class="wow-sr-v5-mobile-filter-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M8 6h10M8 12h7M8 18h4M5 5v14M3 17l2 2 2-2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>Sort by</span><span class="wow-sr-v5-mobile-chevron" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="m7 14 5-5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                    <div class="wow-sr-v5-mobile-filter-body">
                        <button class="wow-sr-v5-mobile-choice" type="button" data-draft-name="sort" data-draft-value="popular"><span class="wow-sr-v5-mobile-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-mobile-choice-copy"><strong>Recommended</strong><span class="wow-sr-v5-mobile-choice-note">Best match</span></span></button>
                        <button class="wow-sr-v5-mobile-choice" type="button" data-draft-name="sort" data-draft-value="rating_desc"><span class="wow-sr-v5-mobile-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-mobile-choice-copy"><strong>Highest rated</strong><span class="wow-sr-v5-mobile-choice-note">Top reviews</span></span></button>
                        <button class="wow-sr-v5-mobile-choice" type="button" data-draft-name="sort" data-draft-value="price_asc"><span class="wow-sr-v5-mobile-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-mobile-choice-copy"><strong>Price: low to high</strong></span></button>
                        <button class="wow-sr-v5-mobile-choice" type="button" data-draft-name="sort" data-draft-value="price_desc"><span class="wow-sr-v5-mobile-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-mobile-choice-copy"><strong>Price: high to low</strong></span></button>
                    </div>
                </section>

                <section class="wow-sr-v5-mobile-filter-section">
                    <button class="wow-sr-v5-mobile-filter-head" type="button" data-mobile-section-toggle aria-expanded="true"><span class="wow-sr-v5-mobile-filter-name"><span class="wow-sr-v5-mobile-filter-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="4" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="4" y="14" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="14" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg></span>Type</span><span class="wow-sr-v5-mobile-chevron" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="m7 14 5-5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                    <div class="wow-sr-v5-mobile-filter-body"><div class="wow-sr-v5-mobile-chip-row">
                        <button class="wow-sr-v5-mobile-chip" type="button" data-draft-name="type" data-draft-value="therapies">Therapy</button>
                        <button class="wow-sr-v5-mobile-chip" type="button" data-draft-name="type" data-draft-value="classes">Class</button>
                        <button class="wow-sr-v5-mobile-chip" type="button" data-draft-name="type" data-draft-value="events">Event</button>
                        <button class="wow-sr-v5-mobile-chip" type="button" data-draft-name="type" data-draft-value="retreats">Retreat</button>
                    </div></div>
                </section>

                <section class="wow-sr-v5-mobile-filter-section">
                    <button class="wow-sr-v5-mobile-filter-head" type="button" data-mobile-section-toggle aria-expanded="true"><span class="wow-sr-v5-mobile-filter-name"><span class="wow-sr-v5-mobile-filter-icon" aria-hidden="true">£</span>Price</span><span class="wow-sr-v5-mobile-chevron" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="m7 14 5-5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                    <div class="wow-sr-v5-mobile-filter-body">
                        <div class="wow-sr-v5-mobile-price-summary"><span>£10 minimum</span><strong data-draft-price-label>Any price</strong><span>£500+</span></div>
                        <input class="wow-sr-v5-mobile-range" type="range" min="10" max="500" step="10" value="500" data-draft-price aria-label="Maximum price">
                        <div class="wow-sr-v5-mobile-chip-row" style="margin-top: 9px">
                            <button class="wow-sr-v5-mobile-chip" type="button" data-draft-price-chip="50">Under £50</button>
                            <button class="wow-sr-v5-mobile-chip" type="button" data-draft-price-chip="100">Under £100</button>
                            <button class="wow-sr-v5-mobile-chip" type="button" data-draft-price-chip="170">Under £170</button>
                        </div>
                    </div>
                </section>

                <section class="wow-sr-v5-mobile-filter-section is-collapsed">
                    <button class="wow-sr-v5-mobile-filter-head" type="button" data-mobile-section-toggle aria-expanded="false"><span class="wow-sr-v5-mobile-filter-name"><span class="wow-sr-v5-mobile-filter-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>Rating</span><span class="wow-sr-v5-mobile-chevron" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="m7 14 5-5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span></button>
                    <div class="wow-sr-v5-mobile-filter-body">
                        <button class="wow-sr-v5-mobile-choice" type="button" data-draft-name="rating" data-draft-value=""><span class="wow-sr-v5-mobile-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-mobile-choice-copy"><strong>Any rating</strong><span class="wow-sr-v5-mobile-choice-note">All results</span></span></button>
                        <button class="wow-sr-v5-mobile-choice" type="button" data-draft-name="rating" data-draft-value="4.5"><span class="wow-sr-v5-mobile-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-mobile-choice-copy"><strong>4.5+ stars</strong><span class="wow-sr-v5-mobile-stars">★★★★★</span></span></button>
                        <button class="wow-sr-v5-mobile-choice" type="button" data-draft-name="rating" data-draft-value="4"><span class="wow-sr-v5-mobile-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-mobile-choice-copy"><strong>4.0+ stars</strong><span class="wow-sr-v5-mobile-stars">★★★★☆</span></span></button>
                        <button class="wow-sr-v5-mobile-choice" type="button" data-draft-name="rating" data-draft-value="reviewed"><span class="wow-sr-v5-mobile-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-mobile-choice-copy"><strong>Reviewed only</strong><span class="wow-sr-v5-mobile-choice-note">1+ review</span></span></button>
                    </div>
                </section>
            </div>
            <footer class="wow-sr-v5-sheet-footer">
                <button class="wow-sr-v5-clear" type="button" data-clear-filters>Clear all</button>
                <button class="wow-sr-v5-apply" type="button" data-apply-filters>Show <span data-apply-count>{{ $mobileResultsCount }}</span> results</button>
            </footer>
        </section>
    </div>
</section>

<script>
        (() => {
            const root = document.getElementById('wowMobileSearch');
            if (!root || root.dataset.initialized === 'true') return;
            root.dataset.initialized = 'true';

            const grid = root.querySelector('#mobileSearchResultsGrid');
            const recommendations = root.querySelector('#mobileSearchRecommendations');
            const pagination = root.querySelector('#mobileSearchResultsPagination');
            const modal = root.querySelector('[data-filter-modal]');
            const mapPanel = root.querySelector('[data-map-panel]');
            const mapCanvas = root.querySelector('[data-map-canvas]');
            const mapKey = @json(config('services.google_maps.key'));
            const countNodes = root.querySelectorAll('[data-result-count]');
            const applyCount = root.querySelector('[data-apply-count]');
            if (!modal) return;
            let draft;
            let activeRequest;
            let map;
            let infoWindow;
            let markers = [];
            let pendingMapData = JSON.parse(root.dataset.initialMap || '[]');

            const escapeHtml = value => String(value || '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char]);
            const activeUrl = () => new URL(window.location.href);
            const priceText = value => Number(value) >= 500 ? 'Any price' : `Under £${value}`;

            function readDraft(url = activeUrl()) {
                return {
                    sort: url.searchParams.get('sort') || 'popular',
                    type: url.searchParams.get('type') || '',
                    rating: url.searchParams.get('rating') || '',
                    price: Math.min(500, Math.max(10, Number(url.searchParams.get('price_max') || 500))),
                };
            }

            function setRange(range, value) {
                const percent = ((Number(value) - Number(range.min)) / (Number(range.max) - Number(range.min))) * 100;
                range.value = value;
                range.style.setProperty('--range-progress', `${percent}%`);
            }

            function renderDraft() {
                root.querySelectorAll('[data-draft-name]').forEach(button => {
                    const selected = draft[button.dataset.draftName] === button.dataset.draftValue;
                    button.classList.toggle('is-selected', selected);
                });
                root.querySelectorAll('[data-draft-price]').forEach(range => setRange(range, draft.price));
                root.querySelectorAll('[data-draft-price-label]').forEach(node => { node.textContent = priceText(draft.price); });
                root.querySelectorAll('[data-draft-price-chip]').forEach(button => button.classList.toggle('is-selected', Number(button.dataset.draftPriceChip) === draft.price));
            }

            function setCounts(count) {
                countNodes.forEach(node => { node.textContent = count; });
                applyCount.textContent = count;
                // Results arrive asynchronously; update the original search
                // event so Search Data does not leave items_shown as Pending.
                window.dispatchEvent(new CustomEvent('wow:searchbar-v4:results-updated', { detail: { count: Number(count) || 0 } }));
            }

            function openFilters() {
                draft = readDraft();
                renderDraft();
                modal.hidden = false;
                document.body.classList.add('wow-sr-v5-no-scroll');
                root.querySelector('[data-close-filters]').focus();
            }

            function closeFilters() {
                modal.hidden = true;
                document.body.classList.remove('wow-sr-v5-no-scroll');
            }

            function setMapMode(enabled) {
                root.classList.toggle('is-map-mode', enabled);
                root.querySelectorAll('[data-map-toggle]').forEach(button => button.setAttribute('aria-pressed', String(enabled)));
                root.querySelectorAll('[data-map-label]').forEach(label => { label.textContent = enabled ? 'List view' : 'Map'; });
                if (enabled) renderMap();
            }

            function loadGoogleMaps() {
                if (window.google && window.google.maps) return Promise.resolve(window.google.maps);
                if (!mapKey) return Promise.reject(new Error('Google Maps is not configured'));
                if (window.wowGoogleMapsPromise) return window.wowGoogleMapsPromise;
                window.wowGoogleMapsPromise = new Promise((resolve, reject) => {
                    const existing = document.getElementById('wow-google-maps-js');
                    if (existing) {
                        existing.addEventListener('load', () => resolve(window.google.maps), { once: true });
                        existing.addEventListener('error', () => reject(new Error('Google Maps failed to load')), { once: true });
                        return;
                    }
                    const script = document.createElement('script');
                    script.id = 'wow-google-maps-js';
                    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapKey)}&v=weekly&region=GB`;
                    script.async = true;
                    script.onload = () => window.google && window.google.maps ? resolve(window.google.maps) : reject(new Error('Google Maps failed to load'));
                    script.onerror = () => reject(new Error('Google Maps failed to load'));
                    document.head.appendChild(script);
                });
                return window.wowGoogleMapsPromise;
            }

            function renderMap() {
                if (!root.classList.contains('is-map-mode')) return;
                loadGoogleMaps().then(maps => {
                    if (!map) {
                        map = new maps.Map(mapCanvas, { center: { lat: 51.5072, lng: -0.1276 }, zoom: 10, mapTypeControl: false, streetViewControl: false, fullscreenControl: false });
                        infoWindow = new maps.InfoWindow();
                    }
                    markers.forEach(marker => marker.setMap(null));
                    markers = [];
                    const bounds = new maps.LatLngBounds();
                    const points = Array.isArray(pendingMapData) ? pendingMapData.filter(item => Number.isFinite(Number(item.lat)) && Number.isFinite(Number(item.lng))) : [];
                    points.forEach(item => {
                        const position = { lat: Number(item.lat), lng: Number(item.lng) };
                        const marker = new maps.Marker({ map, position, title: item.title || '' });
                        marker.addListener('click', () => {
                            const link = item.url ? `<p><a href="${escapeHtml(item.url)}">View offering</a></p>` : '';
                            infoWindow.setContent(`<div class="wow-sr-v5-mobile-map-info"><strong>${escapeHtml(item.title)}</strong><span>${escapeHtml(item.label)}</span>${item.price_label ? `<p>${escapeHtml(item.price_label)}</p>` : ''}${link}</div>`);
                            infoWindow.open({ map, anchor: marker });
                        });
                        markers.push(marker);
                        bounds.extend(position);
                    });
                    if (points.length === 1) { map.setCenter(bounds.getCenter()); map.setZoom(13); }
                    if (points.length > 1) map.fitBounds(bounds, 40);
                    mapPanel.classList.add('is-ready');
                    window.setTimeout(() => maps.event.trigger(map, 'resize'), 0);
                }).catch(() => {
                    mapPanel.classList.remove('is-ready');
                    root.querySelector('[data-map-empty]').textContent = 'Map could not load. Please try again.';
                });
            }

            async function fetchResults(nextUrl, { push = false } = {}) {
                const url = new URL(nextUrl, window.location.origin);
                if (root.dataset.fullNavigation === 'true') {
                    // Category and landing pages render their filtered result set
                    // server-side, so keep the page context and perform a normal
                    // navigation rather than trying to fetch a JSON response.
                    window.location.href = url.toString();
                    return;
                }
                if (activeRequest) activeRequest.abort();
                activeRequest = new AbortController();
                root.setAttribute('aria-busy', 'true');
                try {
                    const response = await fetch(url.toString(), { cache: 'no-store', credentials: 'same-origin', signal: activeRequest.signal, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!response.ok) throw new Error('Search request failed');
                    const payload = await response.json();
                    grid.innerHTML = payload.grid_html || '';
                    if (recommendations) recommendations.innerHTML = payload.recommendations_html || '';
                    pagination.innerHTML = payload.pagination_html || '';
                    pendingMapData = payload.map_data || [];
                    setCounts(payload.count ?? 0);
                    draft = readDraft(url);
                    renderDraft();
                    if (push) window.history.pushState({}, '', url.toString());
                    if (root.classList.contains('is-map-mode')) renderMap();
                } catch (error) {
                    if (error.name !== 'AbortError') console.error('Search results could not be refreshed.', error);
                } finally {
                    if (!activeRequest || !activeRequest.signal.aborted) root.removeAttribute('aria-busy');
                }
            }

            function applyDraft() {
                const url = activeUrl();
                [['sort', draft.sort, 'popular'], ['type', draft.type, ''], ['rating', draft.rating, ''], ['price_max', String(draft.price), '500']].forEach(([name, value, defaultValue]) => {
                    if (!value || value === defaultValue) url.searchParams.delete(name);
                    else url.searchParams.set(name, value);
                });
                url.searchParams.delete('page');
                closeFilters();
                fetchResults(url, { push: true });
            }

            root.addEventListener('click', event => {
                const target = event.target instanceof Element ? event.target : event.target?.parentElement;
                if (!target) return;
                if (target.closest('[data-open-filters]')) { openFilters(); return; }
                if (target.closest('[data-close-filters]')) { closeFilters(); return; }
                if (target.closest('[data-map-toggle]')) { setMapMode(!root.classList.contains('is-map-mode')); return; }
                if (target.closest('[data-clear-filters]')) { draft = { sort: 'popular', type: '', rating: '', price: 500 }; renderDraft(); return; }
                if (target.closest('[data-apply-filters]')) { applyDraft(); return; }
                const sectionToggle = target.closest('[data-mobile-section-toggle]');
                if (sectionToggle) {
                    const section = sectionToggle.closest('.wow-sr-v5-mobile-filter-section');
                    section.classList.toggle('is-collapsed');
                    sectionToggle.setAttribute('aria-expanded', String(!section.classList.contains('is-collapsed')));
                    return;
                }
                const filter = target.closest('[data-draft-name]');
                if (filter) {
                    const name = filter.dataset.draftName;
                    const value = filter.dataset.draftValue;
                    draft[name] = name === 'type' && draft.type === value ? '' : value;
                    renderDraft();
                    return;
                }
                const priceChip = target.closest('[data-draft-price-chip]');
                if (priceChip) { draft.price = Number(priceChip.dataset.draftPriceChip); renderDraft(); return; }
                const pageLink = target.closest('#mobileSearchResultsPagination a');
                if (pageLink && pageLink.href) { event.preventDefault(); fetchResults(pageLink.href, { push: true }); }
            });

            root.querySelectorAll('[data-draft-price]').forEach(range => {
                range.addEventListener('input', () => { draft.price = Number(range.value); renderDraft(); });
            });

            document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeFilters(); });
            window.addEventListener('wow:searchbar-v4:query-change', event => { if (event.detail && event.detail.url) { closeFilters(); fetchResults(event.detail.url); } });
            window.addEventListener('popstate', () => fetchResults(window.location.href));
            draft = readDraft();
            renderDraft();
            if (root.dataset.fullNavigation !== 'true') {
                window.requestAnimationFrame(() => fetchResults(window.location.href));
            }
        })();
</script>
