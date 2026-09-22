@php
    $guideSection = (array) ($guideSection ?? $guidePanel ?? []);
    $eyebrow = $eyebrow ?? ($guideSection['eyebrow'] ?? 'Explore guides');
    $title = $title ?? ($guideSection['title'] ?? 'Guides');
    $summary = $summary ?? ($guideSection['summary'] ?? '');
    $hubUrl = $guideSection['hub_url'] ?? null;
    $hubLabel = $guideSection['hub_label'] ?? 'Browse all guides';
    $links = collect($guideSection['links'] ?? [])->filter(fn ($link): bool => is_array($link))->take(4)->values();
    $sectionId = $id ?? 'wow-guides-section';
@endphp

@if($links->isNotEmpty())
<section class="wow-guides-section" id="{{ $sectionId }}" aria-labelledby="{{ $sectionId }}-heading">
    <div class="container">
        <div class="wow-guides-section__header">
            <div class="wow-guides-section__heading">
                <p class="wow-guides-section__eyebrow">{{ $eyebrow }}</p>
                <h2 id="{{ $sectionId }}-heading">{{ $title }}</h2>
                @if($summary !== '')<p class="wow-guides-section__intro">{{ $summary }}</p>@endif
            </div>
            @if($hubUrl)
                <div class="wow-guides-section__action">
                    @include('partials.wow-button', ['href' => $hubUrl, 'label' => $hubLabel, 'variant' => 'outline', 'size' => 'md', 'arrow' => true])
                </div>
            @endif
        </div>

        <div class="wow-guides-grid">
            @foreach($links as $index => $link)
                @php
                    $linkTitle = $link['title'] ?? $link['label'] ?? 'Guide';
                    $linkUrl = $link['url'] ?? '#';
                    $tag = $link['tag'] ?? ($index === 0 ? 'Start here' : 'Wellbeing');
                    $type = $link['type'] ?? ($title !== '' ? $title : 'Wellness guide');
                @endphp
                <a href="{{ $linkUrl }}" class="wow-guide-card{{ $index === 0 ? ' wow-guide-card--featured' : '' }}">
                    <div class="wow-guide-card__top">
                        <span class="wow-guide-card__tag">{{ $tag }}</span>
                        <span class="wow-guide-card__number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="wow-guide-card__content">
                        <p class="wow-guide-card__type">{{ $type }}</p>
                        <h3>{{ $linkTitle }}</h3>
                        @if(!empty($link['summary']))<p class="wow-guide-card__copy">{{ $link['summary'] }}</p>@endif
                    </div>
                    <div class="wow-guide-card__footer">
                        <span class="btn-wow btn-wow--link btn-arrow wow-guide-card__read">Read guide</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

<style>
    .wow-guides-section { padding: 64px 0 72px; }
    .wow-guides-section__header { display: flex; align-items: flex-end; justify-content: space-between; gap: 40px; margin-bottom: 30px; }
    .wow-guides-section__heading { max-width: 700px; }
    .wow-guides-section__eyebrow { margin: 0 0 10px; color: var(--wow-green, #4f9482); font: 700 11px/1 "Instrument Sans", sans-serif; letter-spacing: .18em; text-transform: uppercase; }
    .wow-guides-section h2 { margin: 0; color: var(--wow-green-dark, #092c25); font: 500 clamp(40px, 4vw, 56px)/1 "Playfair Display", serif; letter-spacing: -.04em; }
    .wow-guides-section__intro { max-width: 680px; margin: 14px 0 0; color: var(--wow-muted, #66736e); font: 15px/1.55 "Instrument Sans", sans-serif; }
    .wow-guides-grid { display: grid; grid-template-columns: 1.2fr repeat(3, minmax(0, 1fr)); gap: 12px; }
    .wow-guide-card { display: flex; min-height: 260px; flex-direction: column; overflow: hidden; padding: 20px; border: 1px solid #dce4e0; border-radius: 4px; background: rgba(255,255,255,.94); color: #17201d; text-decoration: none; transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
    .wow-guide-card:hover { transform: translateY(-3px); border-color: #b7c7c0; box-shadow: 0 16px 38px rgba(17,49,41,.07); color: #17201d; }
    .wow-guide-card--featured { background: linear-gradient(145deg, #e9f4f0, #f8faf9); border-color: #c7ddd5; }
    .wow-guide-card__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 30px; }
    .wow-guide-card__tag { display: inline-flex; min-height: 25px; align-items: center; padding: 0 9px; border-radius: 999px; background: #f2f7f5; color: #092c25; font: 700 9px/1 "Instrument Sans", sans-serif; letter-spacing: .1em; text-transform: uppercase; }
    .wow-guide-card--featured .wow-guide-card__tag { background: #4f9482; color: #fff; }
    .wow-guide-card__number { color: #a5afab; font: 500 12px/1 "Instrument Sans", sans-serif; }
    .wow-guide-card__content { flex: 1; }
    .wow-guide-card__type { margin: 0 0 7px; color: #4f9482; font: 600 10px/1 "Instrument Sans", sans-serif; letter-spacing: .08em; text-transform: uppercase; }
    .wow-guide-card h3 { margin: 0; color: #092c25; font: 500 24px/1.08 "Playfair Display", serif; letter-spacing: -.025em; }
    .wow-guide-card--featured h3 { font-size: 31px; }
    .wow-guide-card__copy { margin: 12px 0 0; color: #66736e; font: 13px/1.5 "Instrument Sans", sans-serif; }
    .wow-guide-card__footer { display: flex; align-items: center; margin-top: 26px; padding-top: 15px; border-top: 1px solid #e8eeeb; }
    .wow-guide-card__read.btn-wow--link { color: #092c25; }
    .wow-guide-card__read.btn-wow--link:hover { color: #4f9482; }
    @media (max-width: 991.98px) { .wow-guides-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .wow-guide-card--featured { grid-column: span 2; min-height: 230px; } }
    @media (max-width: 575.98px) { .wow-guides-section { padding: 42px 0; } .wow-guides-section__header { display: block; margin-bottom: 22px; } .wow-guides-section h2 { font-size: 38px; } .wow-guides-section__intro { font-size: 14px; } .wow-guides-section__action { margin-top: 19px; } .wow-guides-grid { grid-template-columns: 1fr; gap: 9px; } .wow-guide-card--featured { grid-column: auto; } .wow-guide-card { min-height: 0; padding: 17px; } .wow-guide-card__top { margin-bottom: 22px; } .wow-guide-card h3, .wow-guide-card--featured h3 { font-size: 25px; } .wow-guide-card__footer { margin-top: 22px; } }
</style>
@endif
