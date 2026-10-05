@php
    $sectionConfig = is_array($sectionConfig ?? null) ? $sectionConfig : [];
    $collectionSlug = trim((string) ($sectionConfig['collection_slug'] ?? ''));
    $limit = max(1, min(24, (int) ($sectionConfig['limit'] ?? 12)));

    $collectionPayload = $collectionSlug !== ''
        ? app(\App\Services\BackendCollectionsClient::class)->collection($collectionSlug)
        : null;

    $collectionMeta = is_array($collectionPayload['collection'] ?? null)
        ? $collectionPayload['collection']
        : [];

    $collectionProducts = collect((array) ($collectionPayload['data'] ?? []))
        ->filter(fn ($item) => is_array($item))
        ->take($limit)
        ->values();

    $canonicalPath = trim((string) ($collectionMeta['canonical_path'] ?? ''));
    $defaultUrl = $canonicalPath !== ''
        ? '/'.ltrim($canonicalPath, '/')
        : ($collectionSlug !== '' ? '/collections/'.$collectionSlug : '#');

    $title = trim((string) ($sectionConfig['title'] ?? ''))
        ?: trim((string) ($collectionMeta['name'] ?? ''));
    $description = trim((string) ($sectionConfig['description'] ?? ''))
        ?: trim((string) ($collectionMeta['description'] ?? ''));
    $kicker = trim((string) ($sectionConfig['kicker'] ?? 'Collection'));
    $ctaLabel = trim((string) ($sectionConfig['cta_label'] ?? 'Explore collection'));
    $ctaUrl = trim((string) ($sectionConfig['cta_url'] ?? '')) ?: $defaultUrl;

    $sectionId = 'home-collection-'.\Illuminate\Support\Str::slug(
        (string) data_get($section ?? [], 'id', $collectionSlug ?: 'rail')
    );
@endphp

@if($collectionSlug !== '' && $collectionPayload)
    @include('partials.product_showcase_section', [
        'section' => [
            'id' => $sectionId,
            'section_class' => 'section wow-dynamic-collection-section',
            'container_class' => 'container',
            'kicker' => $kicker,
            'title' => $title,
            'description' => $description,
            'cta' => [
                'label' => $ctaLabel,
                'href' => $ctaUrl,
            ],
            'products' => $collectionProducts,
            'page_size' => $limit,
            'show_arrows' => true,
            'empty_text' => 'This collection does not have any live offerings yet.',
        ],
    ])
@endif
