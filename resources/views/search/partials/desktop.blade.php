@php
    $desktopResultsCount = method_exists($products, 'total') ? $products->total() : $products->count();
@endphp

@once
    <style>
        .wow-sr-v5-desktop {
            --sr-green: #4f9a86;
            --sr-green-dark: #2f7464;
            --sr-green-pale: #e4f2ee;
            --sr-ink: #141a2a;
            --sr-copy: #53627a;
            --sr-line: #dfe5ea;
            --sr-page: #fbfaf8;
            color: var(--sr-ink);
            padding: 18px 0 48px;
        }

        .wow-sr-v5-container {
            width: min(1286px, calc(100% - 48px));
            margin: 0 auto;
        }

        .wow-sr-v5-desktop .wow-search-recommendations { margin: 0 0 24px; }
        .wow-sr-v5-desktop .wow-search-recommendations__heading { display: flex; align-items: end; justify-content: space-between; gap: 16px; margin: 0 0 13px; }
        .wow-sr-v5-desktop .wow-search-recommendations__kicker { margin: 0 0 3px; color: #7b8598; font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .wow-sr-v5-desktop .wow-search-recommendations__heading h2 { margin: 0; color: #111827; font-family: "DM Serif Display", Georgia, serif; font-size: 25px; font-weight: 400; line-height: 1.05; }
        .wow-sr-v5-desktop .wow-search-recommendations__heading > span { color: #8c98aa; font-size: 11px; }
        .wow-sr-v5-desktop .wow-search-recommendations__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; align-items: start; }
        .wow-sr-v5-desktop .wow-search-recommendation { position: relative; min-width: 0; padding-top: 14px; }
        .wow-sr-v5-desktop .wow-search-recommendation__label { position: absolute; top: 0; left: 10px; z-index: 5; display: inline-flex; align-items: center; min-height: 24px; padding: 0 9px; border-radius: 999px; background: #e4f2ee; color: #2f7464; font-size: 9px; font-weight: 800; letter-spacing: .04em; white-space: nowrap; }
        .wow-sr-v5-desktop .wow-search-recommendation.is-primary .wow-search-recommendation__label { background: #2f7464; color: #fff; }
        .wow-sr-v5-desktop .wow-search-recommendation .wow49-blade-card, .wow-sr-v5-desktop .wow-search-recommendation .wow49-store-blade { width: 100%; min-width: 0; max-width: none; }
        .wow-sr-v5-desktop .wow-search-recommendations + .wow-sr-v5-grid { margin-top: 4px; }
        .wow-sr-v5-desktop .wow-sr-v5-all-results-title { margin: 22px 0 12px; color: #53627a; font-size: 12px; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }

        @media (max-width: 1220px) and (min-width: 1041px) {
            .wow-sr-v5-desktop .wow-search-recommendations__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        .wow-sr-v5-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 22px;
        }

        .wow-sr-v5-heading {
            margin: 0;
            color: #111827;
            font-family: "DM Serif Display", Georgia, serif;
            font-size: 31px;
            font-weight: 400;
            letter-spacing: 0;
            line-height: 1;
        }

        .wow-sr-v5-heading em { color: #7b8598; font-size: 13px; font-style: normal; letter-spacing: 0; }

        .wow-sr-v5-map-toggle {
            min-height: 37px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 14px;
            border: 1px solid #d5dde4;
            border-radius: 9px;
            background: #fff;
            color: #26334a;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .wow-sr-v5-map-toggle:hover { border-color: #bfd3cd; background: #f7faf9; }

        .wow-sr-v5-layout {
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            gap: 25px;
            align-items: start;
        }

        .wow-sr-v5-sidebar { position: sticky; top: 140px; }

        .wow-sr-v5-filter-card {
            overflow: hidden;
            padding: 14px 14px 18px;
            border: 1px solid #dce3e8;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 12px 30px rgba(16, 24, 40, .045);
        }

        .wow-sr-v5-filter-summary {
            min-height: 58px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border: 1px solid #e7efec;
            border-radius: 13px;
            background: #f4f9f7;
        }

        .wow-sr-v5-summary-icon,
        .wow-sr-v5-filter-icon {
            display: grid;
            place-items: center;
            color: var(--sr-green-dark);
            background: #fff;
        }

        .wow-sr-v5-summary-icon { width: 34px; height: 34px; border-radius: 9px; }
        .wow-sr-v5-summary-copy { min-width: 0; flex: 1; display: grid; gap: 1px; }
        .wow-sr-v5-summary-copy strong { color: #172033; font-size: 12px; font-weight: 750; }
        .wow-sr-v5-summary-copy small { color: #8b98a8; font-size: 9px; font-weight: 600; }
        .wow-sr-v5-summary-count {
            width: 30px;
            height: 30px;
            display: grid;
            flex: 0 0 30px;
            place-items: center;
            border: 1px solid #e3ece9;
            border-radius: 50%;
            background: #fff;
            color: var(--sr-green-dark);
            font-size: 11px;
            font-weight: 700;
        }

        .wow-sr-v5-filter-section { padding: 16px 0; border-bottom: 1px solid #e6ebef; }
        .wow-sr-v5-filter-section:last-child { padding-bottom: 0; border-bottom: 0; }
        .wow-sr-v5-filter-section.is-collapsed .wow-sr-v5-filter-body { display: none; }
        .wow-sr-v5-filter-section.is-collapsed .wow-sr-v5-chevron { transform: rotate(180deg); }

        .wow-sr-v5-filter-head {
            width: 100%;
            min-height: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #1b2434;
            text-align: left;
            cursor: pointer;
        }

        .wow-sr-v5-filter-head:hover { background: #f8faf9; }
        .wow-sr-v5-filter-title { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 750; }
        .wow-sr-v5-filter-icon { width: 25px; height: 25px; border-radius: 7px; background: #f2f6f5; color: #5f746e; }
        .wow-sr-v5-chevron {
            width: 27px;
            height: 27px;
            display: grid;
            flex: 0 0 27px;
            place-items: center;
            border-radius: 50%;
            background: #f4f6f8;
            color: #7e8a9c;
            transition: transform .2s ease;
        }

        .wow-sr-v5-filter-body { display: grid; gap: 5px; margin-top: 10px; }
        .wow-sr-v5-choice {
            width: 100%;
            min-height: 38px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 9px;
            border: 1px solid transparent;
            border-radius: 9px;
            background: transparent;
            color: #425068;
            font-size: 11px;
            text-align: left;
            cursor: pointer;
        }

        .wow-sr-v5-choice:hover { border-color: #e4e9ed; background: #f8faf9; }
        .wow-sr-v5-choice.is-selected { border-color: #d5e9e3; background: #e5f3ef; color: var(--sr-green-dark); }
        .wow-sr-v5-choice-box { width: 17px; height: 17px; flex: 0 0 17px; border: 1px solid #cad3dc; border-radius: 5px; background: #fff; }
        .wow-sr-v5-choice.is-selected .wow-sr-v5-choice-box { border-color: var(--sr-green); background: var(--sr-green); }
        .wow-sr-v5-choice-copy { min-width: 0; flex: 1; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .wow-sr-v5-choice-copy strong { font-size: 11px; font-weight: 650; }
        .wow-sr-v5-choice-note { color: #9aa5b3; font-size: 9px; font-weight: 600; }
        .wow-sr-v5-stars { color: #f2b827; font-size: 9px; letter-spacing: 0; white-space: nowrap; }

        .wow-sr-v5-price-range {
            width: 100%;
            height: 24px;
            margin: 0;
            padding: 0;
            appearance: none;
            background: transparent;
            cursor: pointer;
        }

        .wow-sr-v5-price-range::-webkit-slider-runnable-track { height: 5px; border-radius: 99px; background: linear-gradient(90deg, var(--sr-green) 0 var(--range-progress, 100%), #e0e6ea var(--range-progress, 100%) 100%); }
        .wow-sr-v5-price-range::-moz-range-track { height: 5px; border-radius: 99px; background: #e0e6ea; }
        .wow-sr-v5-price-range::-moz-range-progress { height: 5px; border-radius: 99px; background: var(--sr-green); }
        .wow-sr-v5-price-range::-webkit-slider-thumb { width: 20px; height: 20px; margin-top: -7.5px; appearance: none; border: 2px solid var(--sr-green); border-radius: 50%; background: #fff; box-shadow: 0 2px 6px rgba(16,24,40,.16); }
        .wow-sr-v5-price-range::-moz-range-thumb { width: 18px; height: 18px; border: 2px solid var(--sr-green); border-radius: 50%; background: #fff; }
        .wow-sr-v5-price-labels { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; margin-top: -1px; color: #94a0b0; font-size: 10px; }
        .wow-sr-v5-price-labels span:last-child { text-align: right; }
        .wow-sr-v5-price-value { min-width: 70px; padding: 3px 8px; border: 1px solid #e1e7eb; border-radius: 999px; background: #f8faf9; color: #2f3b50; font-size: 9px; font-weight: 700; text-align: center; }
        .wow-sr-v5-chip-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 7px; margin-top: 4px; }
        .wow-sr-v5-chip {
            min-height: 35px;
            padding: 0 8px;
            border: 1px solid #d6dfe7;
            border-radius: 999px;
            background: #fff;
            color: #34435b;
            font-size: 11px;
            cursor: pointer;
        }
        .wow-sr-v5-chip:hover { border-color: #b9d5cc; background: #f7fbfa; }
        .wow-sr-v5-chip.is-selected { border-color: #9dcbbf; background: #e7f4f0; color: var(--sr-green-dark); font-weight: 650; }

        .wow-sr-v5-content { min-width: 0; }
        .wow-sr-v5-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 20px; align-items: start; }
        .wow-sr-v5-grid > .col-12 { width: auto; max-width: none; padding: 0; }
        .wow-sr-v5-grid .result-view-map { display: none; }
        .wow-sr-v5-grid .result-view-list { display: block; }
        .wow-sr-v5-grid .wow49-blade-card,
        .wow-sr-v5-grid .wow49-store-blade,
        .wow-sr-v5-grid .product-v4-1-ghost-card { width: 100%; min-width: 0; max-width: none; }
        .wow-sr-v5-pagination { margin-top: 26px; }
        .wow-sr-v5-pagination .pagination { justify-content: center; margin-bottom: 0; }

        .wow-sr-v5-map-panel { display: none; position: relative; min-height: 720px; overflow: hidden; border-left: 1px solid #e6eaed; background: #eef0f2; }
        .wow-sr-v5-map-canvas { width: 100%; height: 100%; min-height: 720px; }
        .wow-sr-v5-map-empty { position: absolute; inset: 0; display: grid; place-items: center; padding: 24px; color: #61708a; font-size: 13px; text-align: center; pointer-events: none; }
        .wow-sr-v5-map-panel.is-ready .wow-sr-v5-map-empty { display: none; }
        .wow-sr-v5-map-info { max-width: 230px; color: #26334a; font-family: Inter, sans-serif; font-size: 12px; line-height: 1.4; }
        .wow-sr-v5-map-info strong { display: block; margin-bottom: 4px; color: #141a2a; font-size: 13px; }
        .wow-sr-v5-map-info a { color: var(--sr-green-dark); font-weight: 700; text-decoration: none; }

        .wow-sr-v5-desktop.is-map-mode .wow-sr-v5-container { width: 100%; }
        .wow-sr-v5-desktop.is-map-mode .wow-sr-v5-header { padding: 0 26px; margin-bottom: 12px; }
        .wow-sr-v5-desktop.is-map-mode .wow-sr-v5-layout { grid-template-columns: minmax(460px, 640px) minmax(0, 1fr); gap: 0; align-items: start; }
        .wow-sr-v5-desktop.is-map-mode .wow-sr-v5-sidebar { display: none; }
        .wow-sr-v5-desktop.is-map-mode .wow-sr-v5-content { padding: 0 22px 30px 26px; }
        .wow-sr-v5-desktop.is-map-mode .wow-search-recommendations__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .wow-sr-v5-desktop.is-map-mode .wow-sr-v5-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .wow-sr-v5-desktop.is-map-mode .wow-sr-v5-map-panel {
            position: sticky;
            top: 20px;
            display: block;
            height: calc(100dvh - 40px);
            min-height: 540px;
            align-self: start;
        }
        .wow-sr-v5-desktop.is-map-mode .wow-sr-v5-map-canvas { min-height: 0; }

        @media (max-width: 1220px) {
            .wow-sr-v5-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
@endonce

<section class="wow-sr-v5-desktop" id="wowDesktopSearch" data-search-url="{{ url('/search') }}" data-initial-map='@json($searchMapData ?? [])'>
    <div class="wow-sr-v5-container">
        <header class="wow-sr-v5-header">
            <h1 class="wow-sr-v5-heading">Recommended for you <em><span data-result-count>{{ $desktopResultsCount }}</span> matching offerings</em></h1>
            <button class="wow-sr-v5-map-toggle" type="button" data-map-toggle aria-pressed="false">
                <svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="m3 6 5-2 8 3 5-2v13l-5 2-8-3-5 2V6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 4v13M16 7v13" stroke="currentColor" stroke-width="1.6"/></svg>
                <span data-map-label>Map</span>
            </button>
        </header>

        <div class="wow-sr-v5-layout">
            <aside class="wow-sr-v5-sidebar" aria-label="Refine search results">
                <div class="wow-sr-v5-filter-card">
                    <div class="wow-sr-v5-filter-summary">
                        <span class="wow-sr-v5-summary-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 7h10M18 7h2M4 12h3M11 12h9M4 17h7M15 17h5" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/><circle cx="16" cy="7" r="2" stroke="currentColor" stroke-width="1.6"/><circle cx="9" cy="12" r="2" stroke="currentColor" stroke-width="1.6"/><circle cx="13" cy="17" r="2" stroke="currentColor" stroke-width="1.6"/></svg></span>
                        <span class="wow-sr-v5-summary-copy"><strong>Refine results</strong><small>Filters &amp; Sort</small></span>
                        <span class="wow-sr-v5-summary-count" data-result-count>{{ $desktopResultsCount }}</span>
                    </div>

                    <section class="wow-sr-v5-filter-section">
                        <button class="wow-sr-v5-filter-head" type="button" data-section-toggle aria-expanded="true">
                            <span class="wow-sr-v5-filter-title"><span class="wow-sr-v5-filter-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M8 6h10M8 12h7M8 18h4M5 5v14M3 17l2 2 2-2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>Sort by</span>
                            <span class="wow-sr-v5-chevron" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="m7 14 5-5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        </button>
                        <div class="wow-sr-v5-filter-body">
                            <button class="wow-sr-v5-choice" type="button" data-filter-name="sort" data-filter-value="popular"><span class="wow-sr-v5-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-choice-copy"><strong>Recommended</strong><span class="wow-sr-v5-choice-note">Best match</span></span></button>
                            <button class="wow-sr-v5-choice" type="button" data-filter-name="sort" data-filter-value="rating_desc"><span class="wow-sr-v5-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-choice-copy"><strong>Highest rated</strong><span class="wow-sr-v5-choice-note">Top reviews</span></span></button>
                            <button class="wow-sr-v5-choice" type="button" data-filter-name="sort" data-filter-value="price_asc"><span class="wow-sr-v5-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-choice-copy"><strong>Price: low to high</strong></span></button>
                            <button class="wow-sr-v5-choice" type="button" data-filter-name="sort" data-filter-value="price_desc"><span class="wow-sr-v5-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-choice-copy"><strong>Price: high to low</strong></span></button>
                        </div>
                    </section>

                    <section class="wow-sr-v5-filter-section">
                        <button class="wow-sr-v5-filter-head" type="button" data-section-toggle aria-expanded="true">
                            <span class="wow-sr-v5-filter-title"><span class="wow-sr-v5-filter-icon" aria-hidden="true">£</span>Price</span>
                            <span class="wow-sr-v5-chevron" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="m7 14 5-5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        </button>
                        <div class="wow-sr-v5-filter-body">
                            <input class="wow-sr-v5-price-range" data-price-range type="range" min="10" max="500" step="10" value="500" aria-label="Maximum price">
                            <div class="wow-sr-v5-price-labels"><span>£10</span><strong class="wow-sr-v5-price-value" data-price-value>Any price</strong><span>£500+</span></div>
                            <div class="wow-sr-v5-chip-grid">
                                <button class="wow-sr-v5-chip" type="button" data-price-chip="50">Under £50</button>
                                <button class="wow-sr-v5-chip" type="button" data-price-chip="100">Under £100</button>
                                <button class="wow-sr-v5-chip" type="button" data-price-chip="170">Under £170</button>
                                <button class="wow-sr-v5-chip" type="button" data-price-chip="250">Under £250</button>
                            </div>
                        </div>
                    </section>

                    <section class="wow-sr-v5-filter-section is-collapsed">
                        <button class="wow-sr-v5-filter-head" type="button" data-section-toggle aria-expanded="false">
                            <span class="wow-sr-v5-filter-title"><span class="wow-sr-v5-filter-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="4" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="4" y="14" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="14" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg></span>Type</span>
                            <span class="wow-sr-v5-chevron" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="m7 14 5-5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        </button>
                        <div class="wow-sr-v5-filter-body"><div class="wow-sr-v5-chip-grid">
                            <button class="wow-sr-v5-chip" type="button" data-filter-name="type" data-filter-value="therapies">Therapy</button>
                            <button class="wow-sr-v5-chip" type="button" data-filter-name="type" data-filter-value="classes">Class</button>
                            <button class="wow-sr-v5-chip" type="button" data-filter-name="type" data-filter-value="events">Event</button>
                            <button class="wow-sr-v5-chip" type="button" data-filter-name="type" data-filter-value="retreats">Retreat</button>
                        </div></div>
                    </section>

                    <section class="wow-sr-v5-filter-section is-collapsed">
                        <button class="wow-sr-v5-filter-head" type="button" data-section-toggle aria-expanded="false">
                            <span class="wow-sr-v5-filter-title"><span class="wow-sr-v5-filter-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>Rating</span>
                            <span class="wow-sr-v5-chevron" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="m7 14 5-5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        </button>
                        <div class="wow-sr-v5-filter-body">
                            <button class="wow-sr-v5-choice" type="button" data-filter-name="rating" data-filter-value=""><span class="wow-sr-v5-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-choice-copy"><strong>Any rating</strong><span class="wow-sr-v5-choice-note">All results</span></span></button>
                            <button class="wow-sr-v5-choice" type="button" data-filter-name="rating" data-filter-value="4.5"><span class="wow-sr-v5-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-choice-copy"><strong>4.5+ stars</strong><span class="wow-sr-v5-stars">★★★★★</span></span></button>
                            <button class="wow-sr-v5-choice" type="button" data-filter-name="rating" data-filter-value="4"><span class="wow-sr-v5-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-choice-copy"><strong>4.0+ stars</strong><span class="wow-sr-v5-stars">★★★★☆</span></span></button>
                            <button class="wow-sr-v5-choice" type="button" data-filter-name="rating" data-filter-value="reviewed"><span class="wow-sr-v5-choice-box" aria-hidden="true"></span><span class="wow-sr-v5-choice-copy"><strong>Reviewed only</strong><span class="wow-sr-v5-choice-note">1+ review</span></span></button>
                        </div>
                    </section>
                </div>
            </aside>

            <main class="wow-sr-v5-content" aria-label="Search results">
                <div id="searchRecommendations">
                    {!! $searchRecommendationsHtml ?? '' !!}
                </div>
                <h2 class="wow-sr-v5-all-results-title">All results</h2>
                <div class="wow-sr-v5-grid" id="searchResultsGrid">
                    @include('search.partials.results_cards', ['searchAsyncBoot' => (bool) ($searchAsyncBoot ?? false)])
                </div>
                <div class="wow-sr-v5-pagination" id="searchResultsPagination">
                    @if ($products instanceof \Illuminate\Pagination\Paginator || $products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        {{ $products->withQueryString()->onEachSide(1)->links('pagination::bootstrap-4') }}
                    @endif
                </div>
            </main>

            <section class="wow-sr-v5-map-panel" aria-label="Map view" data-map-panel>
                <div class="wow-sr-v5-map-canvas" data-map-canvas></div>
                <div class="wow-sr-v5-map-empty" data-map-empty>Choose Map to see nearby wellbeing offerings.</div>
            </section>
        </div>
    </div>
</section>

@once
    <script>
        (() => {
            const desktopQuery = window.matchMedia('(min-width: 1041px)');
            if (!desktopQuery.matches) return;

            const root = document.getElementById('wowDesktopSearch');
            if (!root || root.dataset.initialized === 'true') return;
            root.dataset.initialized = 'true';

            const grid = root.querySelector('#searchResultsGrid');
            const recommendations = root.querySelector('#searchRecommendations');
            const pagination = root.querySelector('#searchResultsPagination');
            const mapPanel = root.querySelector('[data-map-panel]');
            const mapCanvas = root.querySelector('[data-map-canvas]');
            const countNodes = root.querySelectorAll('[data-result-count]');
            const mapToggles = root.querySelectorAll('[data-map-toggle]');
            const mapLabels = root.querySelectorAll('[data-map-label]');
            const mapKey = @json(config('services.google_maps.key'));
            let activeRequest;
            let map;
            let infoWindow;
            let markers = [];
            let pendingMapData = JSON.parse(root.dataset.initialMap || '[]');

            const escapeHtml = value => String(value || '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char]);
            const activeUrl = () => new URL(window.location.href);
            const priceText = value => Number(value) >= 500 ? 'Any price' : `Under £${value}`;

            function updateRange(range, value) {
                const percent = ((Number(value) - Number(range.min)) / (Number(range.max) - Number(range.min))) * 100;
                range.value = value;
                range.style.setProperty('--range-progress', `${percent}%`);
            }

            function syncControls(url = activeUrl()) {
                const sort = url.searchParams.get('sort') || 'popular';
                const type = url.searchParams.get('type') || '';
                const rating = url.searchParams.get('rating') || '';
                const price = Math.min(500, Math.max(10, Number(url.searchParams.get('price_max') || 500)));

                root.querySelectorAll('[data-filter-name]').forEach(button => {
                    const name = button.dataset.filterName;
                    const selected = (name === 'sort' && button.dataset.filterValue === sort) ||
                        (name === 'type' && button.dataset.filterValue === type) ||
                        (name === 'rating' && button.dataset.filterValue === rating);
                    button.classList.toggle('is-selected', selected);
                });
                root.querySelectorAll('[data-price-range]').forEach(range => updateRange(range, price));
                root.querySelectorAll('[data-price-value]').forEach(node => { node.textContent = priceText(price); });
                root.querySelectorAll('[data-price-chip]').forEach(button => button.classList.toggle('is-selected', Number(button.dataset.priceChip) === price));
            }

            function setCounts(count) {
                countNodes.forEach(node => { node.textContent = count; });
                // Results arrive asynchronously; update the original search
                // event so Search Data does not leave items_shown as Pending.
                window.dispatchEvent(new CustomEvent('wow:searchbar-v4:results-updated', { detail: { count: Number(count) || 0 } }));
            }

            function setMapMode(enabled) {
                root.classList.toggle('is-map-mode', enabled);
                mapToggles.forEach(button => button.setAttribute('aria-pressed', String(enabled)));
                mapLabels.forEach(label => { label.textContent = enabled ? 'List view' : 'Map'; });
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
                            infoWindow.setContent(`<div class="wow-sr-v5-map-info"><strong>${escapeHtml(item.title)}</strong><span>${escapeHtml(item.label)}</span>${item.price_label ? `<p>${escapeHtml(item.price_label)}</p>` : ''}${link}</div>`);
                            infoWindow.open({ map, anchor: marker });
                        });
                        markers.push(marker);
                        bounds.extend(position);
                    });
                    if (points.length === 1) { map.setCenter(bounds.getCenter()); map.setZoom(13); }
                    if (points.length > 1) map.fitBounds(bounds, 48);
                    mapPanel.classList.add('is-ready');
                    window.setTimeout(() => maps.event.trigger(map, 'resize'), 0);
                }).catch(() => {
                    mapPanel.classList.remove('is-ready');
                    root.querySelector('[data-map-empty]').textContent = 'Map could not load. Please try again.';
                });
            }

            async function fetchResults(nextUrl, { push = false } = {}) {
                const url = new URL(nextUrl, window.location.origin);
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
                    syncControls(url);
                    if (push) window.history.pushState({}, '', url.toString());
                    if (root.classList.contains('is-map-mode')) renderMap();
                } catch (error) {
                    if (error.name !== 'AbortError') console.error('Search results could not be refreshed.', error);
                } finally {
                    if (!activeRequest || !activeRequest.signal.aborted) root.removeAttribute('aria-busy');
                }
            }

            function updateFilter(name, value) {
                const url = activeUrl();
                if (!value || (name === 'sort' && value === 'popular')) url.searchParams.delete(name);
                else url.searchParams.set(name, value);
                url.searchParams.delete('page');
                fetchResults(url, { push: true });
            }

            const compactFilters = window.matchMedia('(max-width: 1220px)');
            function syncCompactFilterSections() {
                if (!compactFilters.matches) return;

                root.querySelectorAll('.wow-sr-v5-filter-section').forEach((section, index) => {
                    const isSortSection = index === 0;
                    section.classList.toggle('is-collapsed', !isSortSection);
                    section.querySelector('[data-section-toggle]')?.setAttribute('aria-expanded', String(isSortSection));
                });
            }

            root.addEventListener('click', event => {
                const sectionToggle = event.target.closest('[data-section-toggle]');
                if (sectionToggle) {
                    const section = sectionToggle.closest('.wow-sr-v5-filter-section');
                    section.classList.toggle('is-collapsed');
                    sectionToggle.setAttribute('aria-expanded', String(!section.classList.contains('is-collapsed')));
                    return;
                }
                if (event.target.closest('[data-map-toggle]')) { setMapMode(!root.classList.contains('is-map-mode')); return; }
                const filter = event.target.closest('[data-filter-name]');
                if (filter) { updateFilter(filter.dataset.filterName, filter.dataset.filterValue); return; }
                const priceChip = event.target.closest('[data-price-chip]');
                if (priceChip) { updateFilter('price_max', priceChip.dataset.priceChip); return; }
                const pageLink = event.target.closest('#searchResultsPagination a');
                if (pageLink && pageLink.href) { event.preventDefault(); fetchResults(pageLink.href, { push: true }); }
            });

            root.querySelectorAll('[data-price-range]').forEach(range => {
                range.addEventListener('input', () => { updateRange(range, range.value); root.querySelectorAll('[data-price-value]').forEach(node => { node.textContent = priceText(range.value); }); });
                range.addEventListener('change', () => updateFilter('price_max', range.value));
            });

            window.addEventListener('wow:searchbar-v4:query-change', event => { if (event.detail && event.detail.url) fetchResults(event.detail.url); });
            window.addEventListener('popstate', () => fetchResults(window.location.href));
            compactFilters.addEventListener('change', syncCompactFilterSections);
            syncCompactFilterSections();
            syncControls();
            window.requestAnimationFrame(() => fetchResults(window.location.href));
        })();
    </script>
@endonce
