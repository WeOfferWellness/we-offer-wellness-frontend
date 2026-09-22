@once
  @push('styles')
    <style>
      .wow-component-container{width:min(calc(100% - 48px),1280px);margin-inline:auto}.wow-modality-discovery{position:relative;padding:33px 0 33px;background:none;border-top:none;border-bottom:none}
      .wow-modality-discovery__header{display:flex;align-items:flex-end;justify-content:space-between;gap:36px;margin-bottom:28px}
      .wow-modality-discovery__heading{max-width:720px}
      .wow-modality-discovery__eyebrow{margin:0 0 9px;color:#4f9482;font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase}
      .wow-modality-discovery h2{margin:0;color:#0b3028;font-family:var(--wow-serif,"Playfair Display",Georgia,serif);font-size:clamp(42px,4.4vw,60px);font-weight:500;line-height:.98;letter-spacing:-.045em}
      .wow-modality-discovery__intro{max-width:650px;margin:13px 0 0;color:#66736e;font-size:15px;line-height:1.55}
      .wow-modality-mosaic{display:grid;grid-template-columns:1fr 1.3fr 1fr;grid-template-rows:180px 180px;gap:14px;min-height:374px}
      .wow-modality-card{position:relative;display:block;overflow:hidden;min-width:0;background:#14231f;border-radius:4px;color:#fff;text-decoration:none;isolation:isolate}
      .wow-modality-card:hover{color:#fff}
      .wow-modality-card--yoga{grid-column:1;grid-row:1}.wow-modality-card--breathwork{grid-column:1;grid-row:2}.wow-modality-card--energy{grid-column:2;grid-row:1/span 2}.wow-modality-card--meditation{grid-column:3;grid-row:1}.wow-modality-card--sound{grid-column:3;grid-row:2}
      .wow-modality-card__image{position:absolute;inset:0;z-index:-2;width:100%;height:100%;object-fit:cover;transform:scale(1.001);transition:transform .45s ease,filter .3s ease}
      .wow-modality-card:hover .wow-modality-card__image{transform:scale(1.045)}
      .wow-modality-card__overlay{position:absolute;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(5,26,21,.06) 5%,rgba(5,26,21,.18) 45%,rgba(5,26,21,.92) 100%)}
      .wow-modality-card--energy .wow-modality-card__overlay{background:linear-gradient(180deg,rgba(5,26,21,.02) 10%,rgba(5,26,21,.12) 45%,rgba(5,26,21,.9) 100%)}
      .wow-modality-card__badge{position:absolute;top:14px;left:14px;z-index:2;display:inline-flex;align-items:center;min-height:27px;padding:0 10px;border-radius:999px;background:#4f9482;color:#fff;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
      .wow-modality-card__content{position:absolute;left:18px;right:18px;bottom:17px}
      .wow-modality-card h3{margin:0;color:#fff;font-family:var(--wow-serif,"Playfair Display",Georgia,serif);font-size:27px;font-weight:500;line-height:1;letter-spacing:-.025em}
      .wow-modality-card--energy h3{font-size:clamp(32px,3vw,43px)}
      .wow-modality-card__description{display:block;max-width:520px;margin:8px 0 0;color:rgba(255,255,255,.86);font-size:12px;line-height:1.45}
      .wow-modality-card__link{display:inline-flex;align-items:center;gap:6px;margin-top:10px;color:#fff;font-size:12px;font-weight:600;line-height:1}
      .wow-modality-card__link-arrow{display:inline-block;transition:transform .18s ease}.wow-modality-card:hover .wow-modality-card__link-arrow{transform:translateX(3px)}
      @media(max-width:991.98px){.wow-modality-discovery{padding:33px 0}.wow-modality-discovery__header{display:block}.wow-modality-discovery__header-action{margin-top:19px}.wow-modality-mosaic{grid-template-columns:1fr 1fr;grid-template-rows:190px 190px 190px;min-height:auto}.wow-modality-card--energy{grid-column:1/span 2;grid-row:1}.wow-modality-card--yoga{grid-column:1;grid-row:2}.wow-modality-card--meditation{grid-column:2;grid-row:2}.wow-modality-card--breathwork{grid-column:1;grid-row:3}.wow-modality-card--sound{grid-column:2;grid-row:3}}
      @media(max-width:575.98px){.wow-modality-discovery{padding:33px 0}.wow-modality-discovery__header{margin-bottom:22px}.wow-modality-discovery h2{font-size:39px}.wow-modality-discovery__intro{font-size:14px}.wow-modality-mosaic{grid-template-rows:210px 160px 160px;gap:9px}.wow-modality-card__content{left:13px;right:13px;bottom:13px}.wow-modality-card h3{font-size:22px}.wow-modality-card--energy h3{font-size:31px}.wow-modality-card__description{display:-webkit-box;overflow:hidden;font-size:11px;-webkit-box-orient:vertical;-webkit-line-clamp:2}.wow-modality-card__link{margin-top:8px;font-size:11px}.wow-modality-card__badge{top:11px;left:11px;min-height:23px;padding:0 8px;font-size:8px}}
      @media(max-width:380px){.wow-modality-mosaic{grid-template-columns:1fr;grid-template-rows:205px repeat(4,150px)}.wow-modality-card--energy,.wow-modality-card--yoga,.wow-modality-card--meditation,.wow-modality-card--breathwork,.wow-modality-card--sound{grid-column:1}.wow-modality-card--energy{grid-row:1}.wow-modality-card--yoga{grid-row:2}.wow-modality-card--meditation{grid-row:3}.wow-modality-card--breathwork{grid-row:4}.wow-modality-card--sound{grid-row:5}}
    </style>
  @endpush
@endonce

@php
  $modalityItems = collect($items ?? [])->filter(fn ($item) => is_array($item))->take((int) ($maxItems ?? 5))->values();
  $sectionKicker = $eyebrow ?? $sectionKicker ?? 'Discover';
  $sectionTitle = $heading ?? $sectionTitle ?? 'Shop wellness by modality';
  $sectionIntro = $intro ?? $sectionDescription ?? 'Explore therapies, classes, workshops and experiences by the kind of support you are looking for.';
  $browseLabel = $browseLabel ?? 'Browse all modalities';
  $browseHref = $browseHref ?? $browseUrl ?? url('/therapies');
  $cardLinkLabel = $cardLinkLabel ?? 'Browse modality';
  $featuredLabel = $featuredLabel ?? 'A place to start';
@endphp

@if($modalityItems->isNotEmpty())
  <section class="wow-modality-discovery" aria-label="{{ $sectionTitle }}">
    <div class="wow-component-container">
      <header class="wow-modality-discovery__header">
        <div class="wow-modality-discovery__heading">
          <p class="wow-modality-discovery__eyebrow">{{ $sectionKicker }}</p>
          <h2>{{ $sectionTitle }}</h2>
          <p class="wow-modality-discovery__intro">{{ $sectionIntro }}</p>
        </div>
        @if($browseHref)
          <div class="wow-modality-discovery__header-action">
            @include('partials.wow-button', ['href' => $browseHref, 'label' => $browseLabel, 'variant' => 'outline', 'size' => 'md', 'arrow' => true])
          </div>
        @endif
      </header>

      <div class="wow-modality-mosaic">
        @foreach($modalityItems as $index => $item)
          @php
            $position = ['energy', 'yoga', 'breathwork', 'meditation', 'sound'][$index] ?? 'sound';
            $name = (string) ($item['name'] ?? 'Wellness modality');
            $href = $item['url'] ?? url('/'.ltrim((string) ($item['slug'] ?? 'therapies'), '/'));
            $image = trim((string) ($item['image_url'] ?? $item['image'] ?? ''));
            $description = (string) ($item['description'] ?? $item['copy'] ?? 'Explore supportive wellness experiences for your next step.');
          @endphp
          <a href="{{ $href }}" class="wow-modality-card wow-modality-card--{{ $position }}" data-loader-init="1">
            @if($image)<img src="{{ $image }}" alt="{{ $name }}" class="wow-modality-card__image" loading="lazy">@endif
            <span class="wow-modality-card__overlay" aria-hidden="true"></span>
            @if($index === 0)<span class="wow-modality-card__badge">{{ $featuredLabel }}</span>@endif
            <span class="wow-modality-card__content">
              <h3>{{ $name }}</h3>
              <span class="wow-modality-card__description">{{ $description }}</span>
              <span class="wow-modality-card__link">{{ $cardLinkLabel }} <span class="wow-modality-card__link-arrow" aria-hidden="true">→</span></span>
            </span>
          </a>
        @endforeach
      </div>
    </div>
  </section>
@endif
