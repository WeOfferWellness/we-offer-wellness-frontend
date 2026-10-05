@props([
    'mode' => 'responsive',
    'options' => [],
    'config' => [],
])

@php
    $mode = in_array($mode, ['desktop', 'mobile', 'responsive'], true) ? $mode : 'responsive';
    $options = is_array($options) ? $options : [];
    $config = is_array($config) ? $config : [];

    if ($config !== []) {
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
            'filterOnly' => false,
            'searchMapData' => [],
            'searchRecommendationsHtml' => '',
            'searchAsyncBoot' => false,
            'searchUrl' => $searchUrl,
            'resultsHeading' => (string) ($config['title'] ?? ($options['resultsHeading'] ?? 'Explore offerings')),
        ]);
    }
@endphp

@if($mode === 'mobile')
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
