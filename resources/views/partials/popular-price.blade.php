@php
    $eyebrow = (string) ($eyebrow ?? 'Explore by budget');
    $heading = (string) ($heading ?? 'Wellness by price');
    $intro = (string) ($intro ?? 'Explore price points with actual availability.');
    $location = trim((string) ($location ?? ''));
    $priceBandOrder = [
        'under_30' => 1,
        '30_59' => 2,
        '60_99' => 3,
        '100_plus' => 4,
    ];
    $priceBands = collect($items ?? [])
        ->filter(fn ($item): bool => is_array($item))
        ->sortBy(fn (array $item): int => $priceBandOrder[(string) ($item['key'] ?? '')] ?? 999)
        ->values();
    $descriptors = [
        'under_30' => 'Accessible wellness',
        '30_59' => 'Everyday wellness',
        '60_99' => 'Popular',
        '100_plus' => 'Premium wellness',
    ];
@endphp

@if($priceBands->isNotEmpty())
<section class="wow-price-discovery" aria-labelledby="{{ $id ?? 'wow-price-discovery-heading' }}">
    <div class="container-page">
        <header class="wow-price-discovery__header">
            <p class="wow-price-discovery__eyebrow">{{ $eyebrow }}</p>
            <h2 id="{{ $id ?? 'wow-price-discovery-heading' }}">{{ $heading }}</h2>
            <p class="wow-price-discovery__intro">{{ $intro }}</p>
        </header>

        <div class="wow-price-grid">
            @foreach($priceBands as $band)
                @php
                    $key = (string) ($band['key'] ?? '');
                    $max = $band['max'] ?? 500;
                    $href = $band['url'] ?? url('/search?where='.urlencode($location).'&price_max='.urlencode((string) $max));
                    $isFeatured = $key === '60_99' || filter_var($band['featured'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $descriptor = $band['descriptor'] ?? ($descriptors[$key] ?? 'Wellness options');
                    $prefix = $key === 'under_30' ? 'Under' : 'From';
                @endphp
                <a href="{{ $href }}" class="wow-price-card{{ $isFeatured ? ' wow-price-card--featured' : '' }}">
                    <div class="wow-price-card__top">
                        @if($isFeatured)
                            <span class="wow-price-card__tag">{{ $band['tag'] ?? 'Popular' }}</span>
                        @else
                            <span class="wow-price-card__label">{{ $descriptor }}</span>
                        @endif
                        <span class="wow-price-card__count">{{ (int) ($band['count'] ?? 0) }} {{ ((int) ($band['count'] ?? 0)) === 1 ? 'option' : 'options' }}</span>
                    </div>
                    <div class="wow-price-card__main">
                        <span class="wow-price-card__prefix">{{ $prefix }}</span>
                        <h3>{{ $band['label'] ?? 'Browse options' }}</h3>
                    </div>
                    <div class="wow-price-card__footer">
                        <span>{{ $band['cta'] ?? 'Explore options' }}</span>
                        <span class="wow-price-card__arrow" aria-hidden="true">→</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

<style>
    .wow-price-discovery { padding: 62px 0 68px; background: #fff; border-top: 1px solid var(--wow-line-soft, #e9eeeb); border-bottom: 1px solid var(--wow-line-soft, #e9eeeb); }
    .wow-price-discovery__header { margin-bottom: 27px; }
    .wow-price-discovery__eyebrow { margin: 0 0 9px; color: var(--wow-green, #4f9482); font: 700 11px/1 "Instrument Sans", sans-serif; letter-spacing: .18em; text-transform: uppercase; }
    .wow-price-discovery h2 { margin: 0; color: var(--wow-green-dark, #0b3028); font: 500 clamp(40px, 4vw, 54px)/1 "Playfair Display", serif; letter-spacing: -.04em; }
    .wow-price-discovery__intro { max-width: 620px; margin: 12px 0 0; color: var(--wow-muted, #68736f); font: 15px/1.5 "Instrument Sans", sans-serif; }
    .wow-price-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
    .wow-price-card { position: relative; display: flex; min-height: 196px; flex-direction: column; padding: 19px; overflow: hidden; background: #fff; border: 1px solid var(--wow-line, #dce4e0); border-radius: 4px; color: var(--wow-text, #17201d); text-decoration: none; transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background-color .18s ease; }
    .wow-price-card:hover { transform: translateY(-3px); border-color: #b7c7c0; box-shadow: 0 14px 34px rgba(18,48,40,.07); color: var(--wow-text, #17201d); }
    .wow-price-card--featured { background: #eef6f3; border-color: #c4dcd4; }
    .wow-price-card--featured:hover { background: #e9f3ef; border-color: #adcfc3; }
    .wow-price-card__top { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .wow-price-card__label { color: var(--wow-muted, #68736f); font: 600 10px/1 "Instrument Sans", sans-serif; letter-spacing: .04em; text-transform: uppercase; }
    .wow-price-card__count { flex: 0 0 auto; color: #8a9490; font: 11px/1 "Instrument Sans", sans-serif; }
    .wow-price-card__tag { display: inline-flex; align-items: center; height: 24px; padding: 0 8px; border-radius: 999px; background: var(--wow-green, #4f9482); color: #fff; font: 700 9px/1 "Instrument Sans", sans-serif; letter-spacing: .08em; text-transform: uppercase; }
    .wow-price-card__main { position: relative; z-index: 1; display: flex; flex: 1; flex-direction: column; justify-content: center; padding: 22px 0 18px; }
    .wow-price-card__prefix { margin-bottom: 4px; color: var(--wow-green, #4f9482); font: 600 11px/1 "Instrument Sans", sans-serif; letter-spacing: .08em; text-transform: uppercase; }
    .wow-price-card h3 { margin: 0; color: var(--wow-green-dark, #0b3028); font: 500 clamp(29px, 2.4vw, 38px)/1 "Playfair Display", serif; letter-spacing: -.035em; }
    .wow-price-card__footer { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 13px; border-top: 1px solid var(--wow-line-soft, #e9eeeb); color: var(--wow-green-dark, #0b3028); font: 600 12px/1 "Instrument Sans", sans-serif; }
    .wow-price-card__arrow { display: inline-flex; align-items: center; justify-content: center; width: 27px; height: 27px; flex: 0 0 auto; border-radius: 50%; background: var(--wow-soft, #f3f7f5); color: var(--wow-green, #4f9482); font-size: 14px; transition: transform .18s ease, background-color .18s ease; }
    .wow-price-card:hover .wow-price-card__arrow { transform: translateX(3px); background: var(--wow-soft-strong, #e8f2ee); }
    @media (max-width: 991.98px) { .wow-price-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575.98px) { .wow-price-discovery { padding: 42px 0; } .wow-price-discovery h2 { font-size: 38px; } .wow-price-discovery__intro { font-size: 14px; } .wow-price-grid { gap: 8px; } .wow-price-card { min-height: 170px; padding: 14px; } .wow-price-card__label { display: none; } .wow-price-card__count { font-size: 10px; } .wow-price-card__main { padding: 19px 0 15px; } .wow-price-card h3 { font-size: 26px; } .wow-price-card__footer { font-size: 11px; } }
</style>
@endif
