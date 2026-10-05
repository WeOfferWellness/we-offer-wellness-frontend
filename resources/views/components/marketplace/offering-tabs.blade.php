@props([
    'options' => [],
    'config' => [],
])

@php
    $options = is_array($options) ? $options : [];
    $config = is_array($config) ? $config : [];

    if ($config !== []) {
        $client = app(\App\Services\BackendDynamicComponentsClient::class);
        $limit = max(1, min((int) ($config['limit'] ?? 12), 24));

        $localPayload = $client->offerings($config, [
            'per_page' => $limit,
        ]);

        $newPayload = $client->offerings($config, [
            'sort' => 'newest',
            'per_page' => $limit,
        ]);

        $onlinePayload = $client->offerings($config, [
            'format' => 'online',
            'per_page' => $limit,
        ]);

        $locationLabel = collect((array) ($config['locations'] ?? []))
            ->filter()
            ->first();

        $options = array_merge($options, [
            'id' => $options['id'] ?? ('dynamic-offering-tabs-'.\Illuminate\Support\Str::random(8)),
            'eyebrow' => (string) ($config['eyebrow'] ?? ($options['eyebrow'] ?? 'Explore wellness')),
            'title' => (string) ($config['title'] ?? ($options['title'] ?? 'Wellness experiences')),
            'intro' => (string) ($config['intro'] ?? ($options['intro'] ?? 'Browse live offerings from trusted practitioners.')),
            'localOfferings' => collect((array) ($localPayload['data'] ?? [])),
            'newOfferings' => collect((array) ($newPayload['data'] ?? [])),
            'onlineOfferings' => collect((array) ($onlinePayload['data'] ?? [])),
            'localLabel' => $options['localLabel'] ?? ($locationLabel ? 'Explore wellness in '.$locationLabel : 'Explore wellness'),
            'newLabel' => $options['newLabel'] ?? ($locationLabel ? 'New in '.$locationLabel : 'New offerings'),
            'onlineLabel' => $options['onlineLabel'] ?? 'Also available online',
            'preferredLocation' => $options['preferredLocation'] ?? $locationLabel,
        ]);
    }
@endphp

@include('partials.offering-tabs', $options)
