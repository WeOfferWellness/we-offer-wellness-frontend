@once
<style>
    .wow-discovery-section{position:relative;overflow:hidden;background:transparent;padding:0;margin-bottom:0}
    .wow-section-heading{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:24px;align-items:end;margin-bottom:28px}
    .wow-section-heading h2,.wow-modality-card h3{margin:0;color:#101828;font-family:var(--wow-serif,"Playfair Display",Georgia,serif);font-weight:500;letter-spacing:-.055em}
    .wow-section-heading h2{max-width:760px;font-size:clamp(44px,5.6vw,72px);line-height:.94}
    .wow-section-heading p{max-width:650px;margin:16px 0 0;color:#596275;font-size:17px;line-height:1.58}
    .wow-modality-board{margin-bottom:72px;padding:8px 0 18px}
    .wow-modality-card{background:rgba(255,255,255,.98);border:1px solid #dfe4ea;box-shadow:0 14px 42px rgba(16,24,40,.055);position:relative;min-height:0;overflow:hidden;border-radius:18px;text-decoration:none;transition:transform 160ms ease,border-color 160ms ease,box-shadow 160ms ease}
    .wow-modality-grid{display:grid;grid-template-columns:repeat(10,minmax(0,1fr));grid-auto-rows:minmax(170px,1fr);gap:14px}
    .wow-modality-card:not(.wow-modality-card--lead){grid-column:span 3;order:2}
    .wow-modality-card--lead{grid-column:4/span 4;grid-row:1/span 2;order:1;border-color:rgba(47,111,96,.42);box-shadow:0 16px 42px rgba(47,111,96,.1)}
    .wow-modality-card--side-left{grid-column:1/span 3}.wow-modality-card--side-right{grid-column:8/span 3}
    .wow-modality-card--side-left.wow-modality-card--top,.wow-modality-card--side-right.wow-modality-card--top{grid-row:1}
    .wow-modality-card--side-left.wow-modality-card--bottom,.wow-modality-card--side-right.wow-modality-card--bottom{grid-row:2}
    .wow-modality-card:hover{transform:translateY(-4px);border-color:rgba(79,147,129,.42);box-shadow:0 18px 44px rgba(16,24,40,.1)}
    .wow-modality-card__image{position:absolute;inset:0}.wow-modality-card__image::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(13,34,31,.04) 22%,rgba(13,34,31,.84) 100%)}
    .wow-modality-card__image img{width:100%;height:100%;display:block;object-fit:cover}
    .wow-modality-card__body{position:absolute;right:0;bottom:0;left:0;z-index:1;display:flex;flex-direction:column;align-items:flex-start;justify-content:flex-end;padding:18px;color:#fff;background:linear-gradient(180deg,transparent 20%,rgba(13,34,31,.58) 100%)}
    .wow-modality-card h3,.wow-modality-card p,.wow-modality-card .wow-card-link{color:#fff}.wow-modality-card h3{font-size:clamp(22px,2vw,30px);line-height:.98}.wow-modality-card p{margin:10px 0 0;font-size:13px;line-height:1.45}.wow-card-link{display:inline-flex;margin-top:auto;padding-top:16px;color:#fff;font-size:13px;font-weight:800;text-underline-offset:.18em}
    .wow-modality-card__badge{position:absolute;top:12px;left:12px;padding:6px 9px;border-radius:999px;background:#2f6f60;color:#fff;font-size:10px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
    @media(max-width:980px){.wow-section-heading{grid-template-columns:1fr}.wow-modality-grid{grid-template-columns:repeat(2,minmax(0,1fr));grid-auto-rows:auto;gap:16px}.wow-modality-card,.wow-modality-card--lead{grid-column:auto!important;grid-row:auto!important;order:initial!important;min-height:350px}}
    @media(max-width:700px){.wow-modality-grid{grid-template-columns:1fr}.wow-modality-card{min-height:260px}.wow-section-heading h2{font-size:clamp(36px,11vw,52px)}}
</style>
@endonce

@php
    $sectionKicker = $sectionKicker ?? 'Discover';
    $sectionTitle = $sectionTitle ?? 'Shop wellness by modality';
    $sectionDescription = $sectionDescription ?? 'Explore therapies, classes, workshops and experiences by the kind of support you are looking for.';
    $browseUrl = $browseUrl ?? url('/therapies');
    $fallback = collect([
        ['name' => 'Breathwork', 'slug' => 'breathwork', 'description' => 'Guided breathing sessions for calm, clarity, nervous-system support and deeper connection with yourself.', 'image_url' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=700&q=80', 'url' => url('/breathwork')],
        ['name' => 'Sound Healing', 'slug' => 'sound-healing', 'description' => 'Immersive sound, vibration and restorative sessions.', 'image_url' => 'https://images.pexels.com/photos/6997998/pexels-photo-6997998.jpeg', 'url' => url('/sound-healing')],
        ['name' => 'Massage', 'slug' => 'massage', 'description' => 'Hands-on support for tension, recovery and rest.', 'image_url' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&w=700&q=80', 'url' => url('/massage')],
        ['name' => 'Yoga', 'slug' => 'yoga', 'description' => 'Classes and sessions for mobility, strength and calm.', 'image_url' => 'https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?auto=format&fit=crop&w=700&q=80', 'url' => url('/yoga')],
        ['name' => 'Coaching', 'slug' => 'coaching', 'description' => 'Guidance for confidence, clarity and personal growth.', 'image_url' => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=700&q=80', 'url' => url('/coaching')],
    ]);
    $items = collect($discoveryCategories ?? [])->filter(fn ($item) => is_array($item))->take(5);
    if ($items->count() < 5) {
        $items = $items->concat($fallback->reject(fn ($fallbackItem) => $items->contains('slug', $fallbackItem['slug'])))->take(5);
    }
@endphp

<section class="wow-discovery-section" aria-label="{{ $sectionTitle }}">
    <div class="container-page wow-container">
        <header class="wow-section-heading">
            <div>
                <p class="wow-kicker">{{ $sectionKicker }}</p>
                <h2>{{ $sectionTitle }}</h2>
                <p>{{ $sectionDescription }}</p>
            </div>
            <a href="{{ $browseUrl }}" class="btn-wow btn-wow--outline btn-arrow" data-loader-init="1"><span class="btn-label">Browse all modalities</span><span class="btn-icon-wrap" aria-hidden="true"><svg class="btn-icon-hover" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"></path></svg><svg class="btn-icon-default" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12l-4 4m4-4-4-4"></path></svg></span></a>
        </header>
        @include('partials.modality-board', ['items' => $items])
    </div>
</section>
