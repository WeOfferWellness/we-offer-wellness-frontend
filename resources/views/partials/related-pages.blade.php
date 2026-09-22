@php
    $relatedId = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($id ?? 'related-pages')) ?: 'related-pages';
    $relatedItems = collect($items ?? [])->filter(fn ($item): bool => is_array($item) && filled($item['href'] ?? null) && filled($item['title'] ?? $item['label'] ?? null))->values();
@endphp

@if($relatedItems->isNotEmpty())
<section class="wow-related-pages" id="{{ $relatedId }}" aria-labelledby="{{ $relatedId }}-title">
    <div class="container">
        <header class="wow-related-pages__header">
            <div>
                <p class="wow-related-pages__eyebrow">{{ $eyebrow ?? 'Keep exploring' }}</p>
                <h2 id="{{ $relatedId }}-title">{{ $heading ?? 'Related pages' }}</h2>
                @if(!empty($intro))<p class="wow-related-pages__intro">{{ $intro }}</p>@endif
            </div>
        </header>
        <div class="wow-related-pages__grid">
            @foreach($relatedItems as $item)
                <a href="{{ $item['href'] }}" class="wow-related-card">
                    <div>
                        @if(!empty($item['type']))<span class="wow-related-card__type">{{ $item['type'] }}</span>@endif
                        <h3>{{ $item['title'] ?? $item['label'] }}</h3>
                        @if(!empty($item['copy']))<p>{{ $item['copy'] }}</p>@endif
                    </div>
                    <span class="wow-related-card__arrow" aria-hidden="true">→</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

@once
<style>
    .wow-related-pages{padding:62px 0 68px;background:#fff;border:0}.wow-related-pages__header{display:flex;align-items:flex-end;justify-content:space-between;gap:32px;margin-bottom:28px}.wow-related-pages__eyebrow{margin:0 0 9px;color:var(--wow-green,#4f9482);font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase}.wow-related-pages h2{margin:0;color:var(--wow-green-dark,#0b3028);font:500 clamp(40px,4vw,54px)/1 "Playfair Display",Georgia,serif;letter-spacing:-.04em}.wow-related-pages__intro{max-width:680px;margin:12px 0 0;color:var(--wow-muted,#68736f);font-size:15px;line-height:1.55}.wow-related-pages__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.wow-related-card{position:relative;display:grid;grid-template-columns:minmax(0,1fr) 34px;align-items:center;gap:18px;min-height:118px;padding:20px;overflow:hidden;background:#fff;border:1px solid var(--wow-line,#dce4e0);border-radius:4px;color:var(--wow-text,#17201d);text-decoration:none;transition:transform .18s ease,border-color .18s ease,background-color .18s ease,box-shadow .18s ease}.wow-related-card:hover{transform:translateY(-2px);border-color:#b8c7c1;background:#fbfdfc;box-shadow:0 12px 30px rgba(18,48,40,.06);color:var(--wow-text,#17201d)}.wow-related-card__type{display:block;margin-bottom:6px;color:var(--wow-green,#4f9482);font-size:9px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}.wow-related-card h3{margin:0;color:var(--wow-text,#17201d);font-size:16px;font-weight:600;line-height:1.3}.wow-related-card p{margin:6px 0 0;color:var(--wow-muted,#68736f);font-size:12px;line-height:1.45}.wow-related-card__arrow{position:relative;z-index:1;display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:var(--wow-soft,#f3f7f5);color:var(--wow-green,#4f9482);font-size:16px;transition:transform .18s ease,background-color .18s ease}.wow-related-card:hover .wow-related-card__arrow{transform:translateX(3px);background:#e8f2ee}
    @media(max-width:575.98px){.wow-related-pages{padding:42px 0}.wow-related-pages h2{font-size:38px}.wow-related-pages__intro{font-size:14px}.wow-related-card{min-height:98px;padding:17px;grid-template-columns:minmax(0,1fr) 30px}.wow-related-card__arrow{width:30px;height:30px}}
</style>
@endonce
@endif
