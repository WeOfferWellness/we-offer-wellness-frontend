@once
  <style>
    .wow-consistent-hero {
      --wow-hero-ink: #10231d;
      --wow-hero-muted: #5d6f68;
      --wow-hero-line: #dfe9e4;
      --wow-hero-green: #0f6b57;
      --wow-hero-soft: #eef8f3;
      position: relative;
      overflow: hidden;
      padding: clamp(28px, 5vw, 64px) 0 clamp(30px, 5vw, 58px);
      color: var(--wow-hero-ink);
      background: transparent;
    }
    .wow-consistent-hero__inner {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: minmax(0, 1.2fr) minmax(280px, .8fr);
      gap: clamp(18px, 3vw, 30px);
      align-items: stretch;
    }
    .wow-consistent-hero--solo .wow-consistent-hero__inner { display: block; }
    .wow-consistent-hero--solo .wow-consistent-hero__copy { max-width: 980px; margin: 0 auto; }
    .wow-consistent-hero__copy,
    .wow-consistent-hero__aside {
      border: 1px solid transparent;
      border-radius: 26px;
      background: transparent;
      box-shadow: none;
    }
    .wow-consistent-hero__copy {
      padding: clamp(24px, 4vw, 42px);
    }
    .wow-consistent-hero__aside {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 18px;
      padding: clamp(22px, 3vw, 32px);
      background: transparent;
    }
    .wow-consistent-hero__eyebrow,
    .wow-consistent-hero__aside-label {
      margin: 0 0 11px;
      color: var(--wow-hero-green);
      font-size: 12px;
      font-weight: 800;
      letter-spacing: .17em;
      line-height: 1.3;
      text-transform: uppercase;
    }
    .wow-consistent-hero h1,
    .wow-consistent-hero h2 {
      margin: 0;
      color: var(--wow-hero-ink);
      font-family: var(--wow-serif, "Playfair Display", Georgia, serif);
      font-weight: 500;
      letter-spacing: -.055em;
    }
    .wow-consistent-hero h1 {
      max-width: 12ch;
      font-size: clamp(42px, 6.2vw, 78px);
      line-height: .94;
    }
    .wow-consistent-hero h2 {
      font-size: clamp(30px, 4vw, 50px);
      line-height: .98;
    }
    .wow-consistent-hero__intro,
    .wow-consistent-hero__aside-copy {
      max-width: 66ch;
      margin: 17px 0 0;
      color: var(--wow-hero-muted);
      font-size: 17px;
      line-height: 1.65;
    }
    .wow-consistent-hero__aside-copy {
      margin-top: 13px;
      font-size: 15px;
      line-height: 1.6;
    }
    .wow-consistent-hero__actions,
    .wow-consistent-hero__chips {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 24px;
    }
    .wow-consistent-hero__chip {
      display: inline-flex;
      align-items: center;
      min-height: 38px;
      padding: 8px 13px;
      border: 1px solid #cfe5da;
      border-radius: 999px;
      background: var(--wow-hero-soft);
      color: var(--wow-hero-green);
      font-size: 13px;
      font-weight: 800;
      text-decoration: none;
    }
    .wow-consistent-hero__aside-items {
      display: grid;
      gap: 9px;
      margin: 20px 0 0;
    }
    .wow-consistent-hero__aside-item {
      display: block;
      padding: 12px 14px;
      border: 1px solid rgba(207, 229, 218, .9);
      border-radius: 15px;
      background: rgba(255, 255, 255, .78);
      color: var(--wow-hero-ink);
      text-decoration: none;
    }
    .wow-consistent-hero__aside-item strong { display: block; font-size: 14px; }
    .wow-consistent-hero__aside-item span { display: block; margin-top: 3px; color: var(--wow-hero-muted); font-size: 12px; }
    .wow-consistent-hero__search { margin-top: 24px; }
    @media (max-width: 820px) {
      .wow-consistent-hero__inner { grid-template-columns: 1fr; }
      .wow-consistent-hero__aside { min-height: 0; }
    }
    @media (max-width: 560px) {
      .wow-consistent-hero { padding-top: 20px; }
      .wow-consistent-hero__copy,
      .wow-consistent-hero__aside { padding: 22px 18px; border-radius: 20px; }
      .wow-consistent-hero h1 { font-size: clamp(40px, 13vw, 58px); }
      .wow-consistent-hero__intro { font-size: 16px; }
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
  $heroHasAside = $heroAsideTitle || $heroAsideText || !empty($heroAsideItems);
@endphp

<section class="wow-consistent-hero{{ !empty($heroImage) ? ' wow-consistent-hero--image' : '' }}{{ !$heroHasAside ? ' wow-consistent-hero--solo' : '' }}" @if(!empty($heroImage)) style="background-image:linear-gradient(rgba(246,251,248,.88),rgba(255,255,255,.94)),url('{{ e($heroImage) }}');background-size:cover;background-position:center;" @endif>
  <div class="container-page wow-consistent-hero__inner">
    <div class="wow-consistent-hero__copy">
      <p class="wow-consistent-hero__eyebrow">{{ $heroEyebrow }}</p>
      <h1>{{ $heroTitle }}</h1>
      @if($heroIntro !== '')<p class="wow-consistent-hero__intro">{{ $heroIntro }}</p>@endif

      @if(!empty($heroActions))
        <div class="wow-consistent-hero__actions">
          @foreach($heroActions as $action)
            <a href="{{ $action['href'] ?? '#' }}" class="btn-wow {{ ($action['style'] ?? 'primary') === 'outline' ? 'btn-wow--outline' : 'btn-wow--cta' }} btn-arrow">
              <span class="btn-label">{{ $action['label'] ?? 'Explore' }}</span>
            </a>
          @endforeach
        </div>
      @endif

      @if(!empty($heroChips))
        <div class="wow-consistent-hero__chips">
          @foreach($heroChips as $chip)
            <a class="wow-consistent-hero__chip" href="{{ $chip['href'] ?? '#' }}">{{ $chip['label'] ?? '' }}</a>
          @endforeach
        </div>
      @endif

      @if(!empty($heroSearchHtml))<div class="wow-consistent-hero__search">{!! $heroSearchHtml !!}</div>@endif
    </div>

    @if($heroHasAside)
      <aside class="wow-consistent-hero__aside">
        <div>
          <p class="wow-consistent-hero__aside-label">{{ $heroAsideLabel }}</p>
          @if($heroAsideTitle)<h2>{{ $heroAsideTitle }}</h2>@endif
          @if($heroAsideText !== '')<p class="wow-consistent-hero__aside-copy">{{ $heroAsideText }}</p>@endif
        </div>
        @if(!empty($heroAsideItems))
          <div class="wow-consistent-hero__aside-items">
            @foreach($heroAsideItems as $item)
              <a class="wow-consistent-hero__aside-item" href="{{ $item['href'] ?? '#' }}">
                <strong>{{ $item['label'] ?? '' }}</strong>
                @if(!empty($item['meta']))<span>{{ $item['meta'] }}</span>@endif
              </a>
            @endforeach
          </div>
        @endif
      </aside>
    @endif
  </div>
</section>
