@php
    $eyebrow = $eyebrow ?? 'Explore nearby';
    $heading = $heading ?? 'Explore nearby locations';
    $intro = $intro ?? 'Discover wellness experiences, therapies and practitioners across nearby areas.';
    $featured = is_array($featured ?? null) ? $featured : null;
    $items = collect($items ?? [])->filter(fn ($item): bool => is_array($item))->values();
    $locationMediaBase = rtrim((string) config('services.location_media_url', 'https://studio.weofferwellness.co.uk'), '/');

    $normaliseImage = static function (?string $path) use ($locationMediaBase): string {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (Str::startsWith(Str::lower($path), ['http://', 'https://', '//'])) {
            return preg_replace(
                '#^https?://(?:atease|testing\.studio|v3)\.weofferwellness\.co\.uk#i',
                $locationMediaBase,
                $path
            ) ?: $path;
        }

        return $locationMediaBase.'/storage/'.ltrim($path, '/');
    };

    $tiles = collect($featured ? [$featured] : [])
        ->merge($items)
        ->filter(fn (array $item): bool => trim((string) ($item['title'] ?? $item['label'] ?? '')) !== '')
        ->unique(fn (array $item): string => (string) ($item['path'] ?? $item['slug'] ?? $item['title'] ?? ''))
        ->take(8)
        ->values();
@endphp

@if($tiles->isNotEmpty())
<section class="wow-location-explorer" aria-labelledby="{{ $id ?? 'wow-location-explorer-heading' }}">
    <div class="container">
      <div class="wow-location-explorer__header">
            <div>
                <p class="wow-location-explorer__eyebrow">{{ $eyebrow }}</p>
                <h2 id="{{ $id ?? 'wow-location-explorer-heading' }}">{{ $heading }}</h2>
                <p class="wow-location-explorer__intro">{{ $intro }}</p>
            </div>
      </div>

      <div class="wow-location-explorer__grid">
            @foreach($tiles as $index => $item)
                @php
                    $title = trim((string) ($item['title'] ?? $item['label'] ?? 'Location'));
                    $path = (string) ($item['path'] ?? '/locations');
                    $href = (string) ($item['url'] ?? url($path));
                    $image = $normaliseImage($item['image_url'] ?? $item['image_path'] ?? null);
                    $isFeatured = $index === 0 && $featured !== null;
                    $isWide = !$isFeatured && (
                        filter_var($item['wide'] ?? false, FILTER_VALIDATE_BOOLEAN)
                        || (string) ($item['layout'] ?? '') === 'wide'
                        || Str::lower($title) === 'chislehurst'
                    );
                    $description = trim((string) ($item['description'] ?? ''));
                    if ($description === '' && isset($item['supply_count'])) {
                        $count = (int) $item['supply_count'];
                        $description = $count.' local '.($count === 1 ? 'offering' : 'offerings');
                    }
                @endphp
                <article class="wow-location-tile{{ $isFeatured ? ' wow-location-tile--featured' : ($isWide ? ' wow-location-tile--wide' : '') }}">
                    @if($image !== '')
                        <img src="{{ $image }}" alt="{{ $title }}" class="wow-location-tile__image" loading="lazy">
                    @endif
                    <div class="wow-location-tile__overlay"></div>
                    <div class="wow-location-tile__content">
                        @if($isFeatured)<span class="wow-location-tile__tag">Featured location</span>@endif
                        <h3>{{ $title }}</h3>
                        @if($description !== '')<p>{{ $description }}</p>@endif
                        @include('partials.wow-button', [
                            'href' => $href,
                            'label' => $isFeatured ? 'Explore '.$title : 'Explore wellness',
                            'variant' => 'link',
                            'size' => 'md',
                            'arrow' => true,
                            'class' => 'wow-location-tile__link',
                        ])
                    </div>
                </article>
            @endforeach
      </div>
    </div>
</section>

<style>
    .wow-location-explorer { padding: 33px 0 33px; }
    .wow-location-explorer__header { display: flex; align-items: flex-end; justify-content: space-between; gap: 36px; margin-bottom: 30px; }
    .wow-location-explorer__eyebrow { margin: 0 0 10px; color: var(--wow-green, #4f9482); font: 700 11px/1 "Instrument Sans", sans-serif; letter-spacing: .18em; text-transform: uppercase; }
    .wow-location-explorer h2 { margin: 0; color: #092c25; font: 500 clamp(40px, 4vw, 56px)/1 "Playfair Display", serif; letter-spacing: -.04em; }
    .wow-location-explorer__intro { max-width: 650px; margin: 13px 0 0; color: var(--wow-muted, #66736e); font: 15px/1.55 "Instrument Sans", sans-serif; }
    .wow-location-explorer__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); grid-auto-rows: 170px; gap: 14px; }
    .wow-location-tile { position: relative; display: block; min-width: 0; overflow: hidden; border-radius: 4px; background: #17201d; color: #fff; isolation: isolate; }
    .wow-location-tile--featured { grid-column: span 2; grid-row: span 2; }
    .wow-location-tile--wide { grid-column: span 2; }
    .wow-location-tile__image { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; transition: transform .45s ease; }
    .wow-location-tile:hover .wow-location-tile__image { transform: scale(1.045); }
    .wow-location-tile__overlay { position: absolute; inset: 0; z-index: -1; background: linear-gradient(180deg, rgba(8,28,23,.04) 10%, rgba(8,28,23,.15) 42%, rgba(6,28,23,.86) 100%); }
    .wow-location-tile__content { position: absolute; right: 20px; bottom: 18px; left: 20px; }
    .wow-location-tile__tag { display: inline-flex; margin-bottom: 10px; padding: 6px 9px; border-radius: 999px; background: rgba(255,255,255,.92); color: var(--wow-green-dark, #0b3028); font: 700 9px/1 "Instrument Sans", sans-serif; letter-spacing: .1em; text-transform: uppercase; }
    .wow-location-tile h3 { margin: 0; color: #fff; font: 500 27px/1.04 "Playfair Display", serif; letter-spacing: -.025em; }
    .wow-location-tile--featured h3 { font-size: clamp(34px, 3vw, 48px); }
    .wow-location-tile__content p { max-width: 440px; margin: 8px 0 0; color: rgba(255,255,255,.83); font: 13px/1.45 "Instrument Sans", sans-serif; }
    .wow-location-tile__link.btn-wow--link { display: inline-flex; margin-top: 10px; color: #fff; }
    .wow-location-tile__link.btn-wow--link:hover { color: #d7fff1; }
    @media (max-width: 991.98px) {
        .wow-location-explorer__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: 180px; }
        .wow-location-tile--featured { grid-column: span 2; grid-row: span 2; }
    }
    @media (max-width: 575.98px) {
        .wow-location-explorer { padding: 33px 0; }
        .wow-location-explorer__header { display: block; margin-bottom: 22px; }
        .wow-location-explorer h2 { font-size: 38px; }
        .wow-location-explorer__intro { font-size: 14px; }
        .wow-location-explorer__grid { grid-template-columns: 1fr; grid-auto-rows: 160px; gap: 10px; }
        .wow-location-tile--featured, .wow-location-tile--wide { grid-column: span 1; grid-row: span 1; }
        .wow-location-tile--featured { min-height: 220px; }
        .wow-location-tile__content { right: 16px; bottom: 15px; left: 16px; }
        .wow-location-tile h3, .wow-location-tile--featured h3 { font-size: 27px; }
        .wow-location-tile__content p { display: none; }
    }
</style>
@endif
