@once
  <style>
    .wow-consistent-hero {
      --wow-green: #006b57;
      --wow-green-dark: #082f27;
      --wow-text: #17201d;
      --wow-muted: #67736f;
      --wow-line: #dfe5e2;
      --wow-bg: #f8faf9;
      --btn-outline-border: #cfd9d5;
      position: relative;
      overflow: hidden;
      color: var(--wow-text);
      background: linear-gradient(90deg, rgba(0, 107, 87, .035), rgba(0, 107, 87, 0) 40%), var(--wow-bg);
      border-bottom: 1px solid var(--wow-line);
    }
    .wow-consistent-hero__inner {
      display: grid;
      grid-template-columns: minmax(0, 1.25fr) minmax(340px, .75fr);
      min-height: 280px;
    }
    .wow-consistent-hero__copy {
      display: flex;
      flex-direction: column;
      justify-content: center;
      min-width: 0;
      padding: 52px clamp(42px, 6vw, 88px) 52px 0;
    }
    .wow-consistent-hero__eyebrow,
    .wow-consistent-hero__aside-label {
      margin: 0 0 11px;
      color: var(--wow-green);
      font-size: 11px;
      font-weight: 700;
      line-height: 1;
      letter-spacing: .2em;
      text-transform: uppercase;
    }
    .wow-consistent-hero h1,
    .wow-consistent-hero h2 {
      margin: 0;
      color: #092c25;
      font-family: "Playfair Display", Georgia, serif;
      font-weight: 500;
      letter-spacing: -.045em;
    }
    .wow-consistent-hero h1 { font-size: clamp(52px, 5vw, 76px); line-height: .98; }
    .wow-consistent-hero h2 {
      max-width: 390px;
      font-size: clamp(32px, 3vw, 46px);
      line-height: 1.03;
      letter-spacing: -.035em;
    }
    .wow-consistent-hero__intro,
    .wow-consistent-hero__aside-copy {
      max-width: 640px;
      margin: 18px 0 0;
      color: var(--wow-muted);
      font-size: clamp(16px, 1.35vw, 18px);
      line-height: 1.55;
    }
    .wow-consistent-hero__aside-copy { max-width: 400px; margin-top: 15px; font-size: 15px; }
    .wow-consistent-hero__actions,
    .wow-consistent-hero__chips {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 25px;
    }
    .wow-consistent-hero .btn-wow {
      --btn-ink: #17201d;
      --btn-bg: #fff;
      display: inline-flex;
      position: relative;
      align-items: center;
      justify-content: center;
      gap: 3px;
      min-height: 42px;
      padding: 0 18px;
      border: 1px solid var(--btn-outline-border);
      border-radius: 999px;
      outline: 0;
      color: var(--btn-ink);
      background: var(--btn-bg);
      font-size: 14px;
      font-weight: 500;
      line-height: 1;
      text-decoration: none;
      transition: background-color .18s ease, border-color .18s ease, transform .18s ease;
    }
    .wow-consistent-hero .btn-wow:hover { border-color: #9eb0aa; color: var(--wow-green-dark); background: #f3f7f5; }
    .wow-consistent-hero .btn-wow--primary,
    .wow-consistent-hero .btn-wow--cta { border-color: #4f9482; color: #fff; background: #4f9482; }
    .wow-consistent-hero .btn-wow--primary:hover,
    .wow-consistent-hero .btn-wow--cta:hover { border-color: #3f806f; color: #fff; background: #3f806f; }
    .wow-consistent-hero .btn-arrow { padding-right: 12px; }
    .wow-consistent-hero .btn-arrow::after {
      content: "→";
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 22px;
      height: 22px;
      margin-left: 6px;
      border-radius: 50%;
      color: var(--wow-green);
      background: #f0f5f3;
      font-size: 14px;
      line-height: 1;
      transition: transform .18s ease;
    }
    .wow-consistent-hero .btn-wow--primary.btn-arrow::after,
    .wow-consistent-hero .btn-wow--cta.btn-arrow::after { color: #fff; background: rgba(255, 255, 255, .15); }
    .wow-consistent-hero .btn-arrow:hover::after { transform: translateX(2px); }
    .wow-consistent-hero__aside {
      display: flex;
      align-items: center;
      min-width: 0;
      padding: 50px 0 50px clamp(42px, 5vw, 70px);
      border-left: 1px solid var(--wow-line);
    }
    .wow-consistent-hero__aside > div { max-width: 400px; }
    .wow-consistent-hero__aside-items { display: grid; gap: 9px; margin-top: 20px; }
    .wow-consistent-hero__aside-item {
      display: block;
      padding: 12px 14px;
      border: 1px solid rgba(207, 229, 218, .9);
      border-radius: 15px;
      color: var(--wow-text);
      background: rgba(255, 255, 255, .78);
      text-decoration: none;
    }
    .wow-consistent-hero__aside-item strong { display: block; font-size: 14px; }
    .wow-consistent-hero__aside-item span { display: block; margin-top: 3px; color: var(--wow-muted); font-size: 12px; }
    .wow-consistent-hero__location { position: relative; min-height: 280px; overflow: hidden; border-left: 1px solid var(--wow-line); }
    .wow-consistent-hero__location-image { display: block; position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center; }
    .wow-consistent-hero__location::after { content: ""; position: absolute; inset: 0; pointer-events: none; background: linear-gradient(90deg, rgba(248, 250, 249, .06), rgba(248, 250, 249, 0)); }
    .wow-location-image-label {
      display: inline-flex;
      position: absolute;
      z-index: 2;
      left: 22px;
      bottom: 20px;
      align-items: center;
      gap: 7px;
      padding: 8px 12px;
      border: 1px solid rgba(220, 228, 224, .9);
      border-radius: 999px;
      color: #24342f;
      background: rgba(255, 255, 255, .94);
      font-size: 12px;
      font-weight: 500;
      backdrop-filter: blur(6px);
    }
    .wow-location-image-label__dot { width: 6px; height: 6px; border-radius: 50%; background: var(--wow-green); }
    .wow-consistent-hero__chips { margin-top: 20px; }
    .wow-consistent-hero__chip { display: inline-flex; align-items: center; min-height: 38px; padding: 8px 13px; border: 1px solid #cfe5da; border-radius: 999px; color: var(--wow-green); background: #eef8f3; font-size: 13px; font-weight: 700; text-decoration: none; }
    .wow-consistent-hero__search { margin-top: 24px; }
    .wow-consistent-hero--location h1,
    .wow-consistent-hero--location .wow-consistent-hero__intro {
      text-align: left !important;
    }
    @media (max-width: 991.98px) {
      .wow-consistent-hero__inner { grid-template-columns: 1fr; min-height: auto; }
      .wow-consistent-hero__copy { padding: 42px 0 36px; }
      .wow-consistent-hero__aside { padding: 30px 0 36px; border-top: 1px solid var(--wow-line); border-left: 0; }
      .wow-consistent-hero__location { height: 180px; min-height: 180px; border-top: 1px solid var(--wow-line); border-left: 0; }
    }
    @media (max-width: 575.98px) {
      .wow-consistent-hero { background: #f8faf9; }
      .wow-consistent-hero__copy { padding: 32px 0 30px; }
      .wow-consistent-hero__eyebrow { margin-bottom: 10px; font-size: 10px; letter-spacing: .18em; }
      .wow-consistent-hero h1 { font-size: clamp(45px, 14vw, 58px); }
      .wow-consistent-hero__intro { margin-top: 14px; font-size: 15px; line-height: 1.5; }
      .wow-consistent-hero__actions { margin-top: 21px; }
      .wow-consistent-hero__aside { padding: 24px 0 28px; }
      .wow-consistent-hero__aside-label { margin-bottom: 10px; font-size: 10px; }
      .wow-consistent-hero h2 { max-width: 310px; font-size: 31px; }
      .wow-consistent-hero__aside-copy { margin-top: 11px; font-size: 14px; }
      .wow-consistent-hero__location { height: 155px; min-height: 155px; }
      .wow-location-image-label { left: 14px; bottom: 13px; padding: 7px 10px; font-size: 11px; }
    }
  </style>
@endonce

@php
  $heroEyebrow = $heroEyebrow ?? 'We Offer Wellness';
  $heroTitle = $heroTitle ?? 'Wellness, made easier to explore';
  $heroIntro = $heroIntro ?? '';
  $heroAsideTitle = $heroAsideTitle ?? null;
  $heroAsideLabel = $heroAsideLabel ?? 'A calmer way to browse';
  $heroAsideText = $heroAsideText ?? 'Clear pathways into trusted wellness experiences, wherever you are.';
  $heroActions = $heroActions ?? [];
  $heroChips = $heroChips ?? [];
  $heroAsideItems = $heroAsideItems ?? [];
  $heroImage = trim((string) ($heroImage ?? ''));
  if ($heroImage !== '') {
    $heroMediaBase = rtrim((string) config('services.location_media_url', 'https://studio.weofferwellness.co.uk'), '/');
    if (!\Illuminate\Support\Str::startsWith(\Illuminate\Support\Str::lower($heroImage), ['http://', 'https://', '//'])) {
      $heroImage = $heroMediaBase.'/storage/'.ltrim($heroImage, '/');
    }
  }
  $heroLocationLabel = trim((string) ($heroLocationLabel ?? $heroTitle));
  $heroHasLocation = $heroImage !== '';
  $heroIsLocation = (bool) ($heroIsLocation ?? $heroHasLocation);
  $heroHasAside = $heroAsideTitle || $heroAsideText || !empty($heroAsideItems);
@endphp

<section class="wow-consistent-hero{{ $heroIsLocation ? ' wow-consistent-hero--location' : '' }}">
  <div class="container-page wow-consistent-hero__inner">
    <div class="wow-consistent-hero__copy">
      <p class="wow-consistent-hero__eyebrow">{{ $heroEyebrow }}</p>
      <h1>{{ $heroTitle }}</h1>
      @if($heroIntro !== '')<p class="wow-consistent-hero__intro">{{ $heroIntro }}</p>@endif
      @if(!empty($heroActions))
        <div class="wow-consistent-hero__actions">
          @foreach($heroActions as $action)
            <a href="{{ $action['href'] ?? '#' }}" class="btn-wow {{ ($action['style'] ?? 'primary') === 'outline' ? 'btn-wow--outline' : 'btn-wow--primary' }} btn-arrow"><span class="btn-label">{{ $action['label'] ?? 'Explore' }}</span></a>
          @endforeach
        </div>
      @endif
      @if(!empty($heroChips))
        <div class="wow-consistent-hero__chips">
          @foreach($heroChips as $chip)<a class="wow-consistent-hero__chip" href="{{ $chip['href'] ?? '#' }}">{{ $chip['label'] ?? '' }}</a>@endforeach
        </div>
      @endif
      @if(!empty($heroSearchHtml))<div class="wow-consistent-hero__search">{!! $heroSearchHtml !!}</div>@endif
    </div>

    @if($heroHasLocation)
      <aside class="wow-consistent-hero__location" aria-label="{{ $heroLocationLabel }} location">
        <img class="wow-consistent-hero__location-image" src="{{ e($heroImage) }}" alt="{{ $heroLocationLabel }} and surrounding area">
        <div class="wow-location-image-label"><span class="wow-location-image-label__dot"></span>{{ $heroLocationLabel }}</div>
      </aside>
    @elseif($heroHasAside)
      <aside class="wow-consistent-hero__aside">
        <div>
          <p class="wow-consistent-hero__aside-label">{{ $heroAsideLabel }}</p>
          @if($heroAsideTitle)<h2>{{ $heroAsideTitle }}</h2>@endif
          @if($heroAsideText !== '')<p class="wow-consistent-hero__aside-copy">{{ $heroAsideText }}</p>@endif
        </div>
        @if(!empty($heroAsideItems))
          <div class="wow-consistent-hero__aside-items">
            @foreach($heroAsideItems as $item)
              <a class="wow-consistent-hero__aside-item" href="{{ $item['href'] ?? '#' }}"><strong>{{ $item['label'] ?? '' }}</strong>@if(!empty($item['meta']))<span>{{ $item['meta'] }}</span>@endif</a>
            @endforeach
          </div>
        @endif
      </aside>
    @endif
  </div>
</section>
