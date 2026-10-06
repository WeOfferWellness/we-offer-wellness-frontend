@props([
    'mode' => 'responsive',
    'options' => [],
    'config' => [],
])

@php
    $mode = in_array($mode, ['desktop', 'mobile', 'responsive'], true) ? $mode : 'responsive';
    $options = is_array($options) ? $options : [];
    $config = is_array($config) ? $config : [];
    $isDynamic = $config !== [];
    $payload = [];
    $products = collect();
    $total = 0;

    if ($isDynamic) {
        $dynamicConfig = $config;
        $runtimeType = trim((string) request()->query('type', ''));

        if ($runtimeType !== '') {
            $dynamicConfig['offering_types'] = collect((array) ($dynamicConfig['offering_types'] ?? []))
                ->push($runtimeType)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $payload = app(\App\Services\BackendDynamicComponentsClient::class)->offerings(
            $dynamicConfig,
            [
                'format' => request()->query('format'),
                'location' => request()->query('location'),
                'sort' => request()->query('sort', 'default'),
                'page' => request()->query('page', 1),
                'per_page' => $config['per_page'] ?? 12,
            ]
        );

        $products = collect((array) ($payload['data'] ?? []));
        $total = (int) data_get($payload, 'meta.total', $products->count());
        $searchUrl = request()->url();

        $options = array_merge($options, [
            'products' => $products,
            'resultCount' => $total,
            'desktopResultsCount' => $total,
            'mobileResultsCount' => $total,
            'desktopFullNavigation' => true,
            'mobileFullNavigation' => true,
            'showMap' => false,
            'mobileShowMap' => false,
            'filterOnly' => true,
            'searchMapData' => [],
            'searchRecommendationsHtml' => '',
            'searchAsyncBoot' => false,
            'searchUrl' => $searchUrl,
            'resultsHeading' => (string) ($config['title'] ?? ($options['resultsHeading'] ?? 'Explore offerings')),
        ]);
    }
@endphp

@if($isDynamic && $mode === 'responsive')
    @php
        $currentPage = max(1, (int) data_get($payload, 'meta.current_page', request()->query('page', 1)));
        $lastPage = max(1, (int) data_get($payload, 'meta.last_page', 1));
        $heading = trim((string) ($config['title'] ?? '')) ?: 'Explore offerings';
        $query = request()->query();
    @endphp

    <div class="wow-dynamic-offering-results">
        <style>
            .wow-dynamic-offering-results__layout{display:grid;grid-template-columns:250px minmax(0,1fr);gap:25px;align-items:start}
            .wow-dynamic-offering-results__desktop .wow-sr-v5-desktop{padding:0}
            .wow-dynamic-offering-results__desktop .wow-sr-v5-container{width:100%}
            .wow-dynamic-offering-results__desktop .wow-sr-v5-header{display:none}
            .wow-dynamic-offering-results__desktop .wow-sr-v5-layout{display:block}
            .wow-dynamic-offering-results__desktop .wow-sr-v5-sidebar{position:sticky;top:140px}
            .wow-dynamic-offering-results__content{min-width:0}
            .wow-dynamic-offering-results__head{display:flex;justify-content:space-between;align-items:end;gap:16px;margin-bottom:18px}
            .wow-dynamic-offering-results__head h2{margin:0}
            .wow-dynamic-offering-results__count{color:#525252;font-size:.875rem}
            .wow-dynamic-offering-results__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
            .wow-dynamic-offering-results__empty{padding:24px;border:1px solid #e0e0e0;background:#fff;color:#525252}
            .wow-dynamic-offering-results__pagination{display:flex;justify-content:center;align-items:center;gap:12px;margin-top:24px}
            @media(max-width:1040px){
                .wow-dynamic-offering-results__layout{display:block}
                .wow-dynamic-offering-results__desktop{display:none}
                .wow-dynamic-offering-results__grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            }
            @media(max-width:640px){
                .wow-dynamic-offering-results__grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
                .wow-dynamic-offering-results__head{align-items:start;flex-direction:column}
            }
        </style>

        @include('search.partials.mobile', array_merge($options, [
            'mobileResultsCount' => $total,
            'filterOnly' => true,
        ]))

        <div class="wow-dynamic-offering-results__layout">
            <aside class="wow-dynamic-offering-results__desktop">
                @include('search.partials.desktop', array_merge($options, [
                    'desktopResultsCount' => $total,
                    'filterOnly' => true,
                ]))
            </aside>

            <div class="wow-dynamic-offering-results__content">
                <header class="wow-dynamic-offering-results__head">
                    <h2>{{ $heading }}</h2>
                    <span class="wow-dynamic-offering-results__count">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('offering', $total) }}</span>
                </header>

                @if($products->isNotEmpty())
                    <div class="wow-dynamic-offering-results__grid">
                        @foreach($products as $product)
                            @include('partials.product_card_v4_1', [
                                'product' => $product,
                                'preferredLocation' => request()->query('location'),
                            ])
                        @endforeach
                    </div>

                    @if($lastPage > 1)
                        <nav class="wow-dynamic-offering-results__pagination" aria-label="Offering result pages">
                            @if($currentPage > 1)
                                <a class="btn-wow btn-wow--outline btn-sm" href="{{ request()->url().'?'.http_build_query(array_merge($query, ['page' => $currentPage - 1])) }}">← Previous</a>
                            @endif
                            <span>Page {{ $currentPage }} of {{ $lastPage }}</span>
                            @if($currentPage < $lastPage)
                                <a class="btn-wow btn-wow--outline btn-sm" href="{{ request()->url().'?'.http_build_query(array_merge($query, ['page' => $currentPage + 1])) }}">Next →</a>
                            @endif
                        </nav>
                    @endif
                @else
                    <div class="wow-dynamic-offering-results__empty">
                        No live offerings match this component configuration yet.
                    </div>
                @endif
            </div>
        </div>
    </div>
@elseif($mode === 'mobile')
    @include('search.partials.mobile', $options)
@elseif($mode === 'desktop')
    @include('search.partials.desktop', $options)
@else
    @include('search.partials.mobile', array_merge($options, [
        'mobileResultsCount' => $options['mobileResultsCount'] ?? $options['resultCount'] ?? collect($options['products'] ?? [])->count(),
    ]))
    @include('search.partials.desktop', array_merge($options, [
        'desktopResultsCount' => $options['desktopResultsCount'] ?? $options['resultCount'] ?? collect($options['products'] ?? [])->count(),
    ]))
@endif
