{{-- resources/views/needs/index.blade.php --}}
@extends('layouts.app')

@push('head')
  <title>{{ $seo['title'] ?? 'Browse by Need | We Offer Wellness®' }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
  <style>
    .wow-needs-page{
      position:relative;
      overflow:hidden;
      background:#fff;
      color:#101828;
      padding:64px 0 76px;
    }
    .wow-page-grid{
      position:absolute;
      inset:0;
      width:min(100% - 40px, 1280px);
      margin:0 auto;
      pointer-events:none;
      border-left:1px solid rgba(17,24,39,.08);
      border-right:1px solid rgba(17,24,39,.08);
      background-image:
        linear-gradient(to right, transparent calc(25% - 1px), rgba(17,24,39,.10) calc(25% - 1px), rgba(17,24,39,.10) 25%, transparent 25%),
        linear-gradient(to right, transparent calc(50% - 1px), rgba(17,24,39,.10) calc(50% - 1px), rgba(17,24,39,.10) 50%, transparent 50%),
        linear-gradient(to right, transparent calc(75% - 1px), rgba(17,24,39,.10) calc(75% - 1px), rgba(17,24,39,.10) 75%, transparent 75%);
    }
    .wow-page-grid::before,
    .wow-page-grid::after{
      content:"";
      position:absolute;
      top:0;
      bottom:0;
      width:1px;
      border-left:1px dashed rgba(17,24,39,.16);
    }
    .wow-page-grid::before{ left:25%; }
    .wow-page-grid::after{ left:75%; }
    .wow-needs-container{
      position:relative;
      z-index:1;
      width:min(100% - 40px, 1280px);
      margin:0 auto;
    }
    .wow-needs-hero{
      display:grid;
      grid-template-columns:minmax(0,.95fr) minmax(280px,.55fr);
      gap:48px;
      align-items:end;
      margin-bottom:30px;
    }
    .wow-kicker{
      margin:0 0 10px;
      color:#344054;
      font-size:13px;
      font-weight:300;
      letter-spacing:.16em;
      text-transform:uppercase;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-needs-hero h1,
    .wow-need-card h2,
    .wow-trust-panel h2{
      margin:0;
      color:#101828;
      font-family:'Playfair Display', Georgia, serif;
      font-weight:500;
      letter-spacing:-.055em;
    }
    .wow-needs-hero h1{
      max-width:760px;
      font-size:clamp(58px, 7.2vw, 96px);
      line-height:.94;
    }
    .wow-needs-hero p{
      max-width:680px;
      margin:16px 0 0;
      color:#596275;
      font-size:18px;
      line-height:1.55;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-hero-note{
      background:rgba(255,255,255,.96);
      border:1px solid #dfe4ea;
      border-radius:4px;
      padding:18px;
      box-shadow:0 12px 34px rgba(16,24,40,.035);
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-hero-note strong{
      display:block;
      margin-bottom:6px;
      color:#101828;
      font-size:15px;
      font-weight:700;
    }
    .wow-hero-note span{
      display:block;
      color:#667085;
      font-size:14px;
      line-height:1.5;
    }
    .wow-needs-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0,1fr));
      gap:22px;
      margin-bottom:28px;
    }
    .wow-need-card{
      min-height:320px;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      background:rgba(255,255,255,.96);
      border:1px solid #dfe4ea;
      border-radius:4px;
      box-shadow:0 12px 34px rgba(16,24,40,.035);
    }
    .wow-need-card__inner{ padding:24px; }
    .wow-need-card h2{
      max-width:520px;
      font-size:clamp(32px, 3.6vw, 48px);
      line-height:.98;
    }
    .wow-need-card p{
      max-width:590px;
      margin:16px 0 0;
      color:#596275;
      font-size:16px;
      line-height:1.55;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-need-card__footer{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      padding:18px 24px;
      border-top:1px solid #edf0f2;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-need-card__footer small{
      color:#667085;
      font-size:13px;
      line-height:1.45;
    }
    .wow-needs-page .btn-wow{
      min-height:42px;
      padding:0 22px;
      border-radius:4px;
      font-size:15px;
      font-weight:600;
      white-space:nowrap;
    }
    .wow-quick-browse{
      display:grid;
      grid-template-columns:repeat(4, minmax(0,1fr));
      gap:16px;
      margin-bottom:28px;
    }
    .wow-quick-card{
      display:flex;
      min-height:150px;
      flex-direction:column;
      justify-content:space-between;
      background:rgba(255,255,255,.96);
      border:1px solid #dfe4ea;
      border-radius:4px;
      padding:18px;
      text-decoration:none;
      box-shadow:0 10px 30px rgba(16,24,40,.03);
      transition:border-color 160ms ease, transform 160ms ease;
    }
    .wow-quick-card:hover{
      border-color:#4f9381;
      transform:translateY(-2px);
    }
    .wow-quick-card h3{
      margin:0;
      color:#101828;
      font-family:'Playfair Display', Georgia, serif;
      font-weight:500;
      font-size:28px;
      line-height:1;
      letter-spacing:-.04em;
    }
    .wow-quick-card p{
      margin:12px 0 0;
      color:#596275;
      font-size:14px;
      line-height:1.45;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-quick-card span{
      margin-top:16px;
      color:#4f9381;
      font-size:14px;
      font-weight:700;
      font-family:'Manrope', system-ui, sans-serif;
    }
    .wow-trust-panel{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:28px;
      background:rgba(255,255,255,.96);
      border:1px solid #dfe4ea;
      border-radius:4px;
      padding:26px 28px;
      box-shadow:0 12px 34px rgba(16,24,40,.035);
    }
    .wow-trust-panel h2{
      font-size:clamp(32px, 3.8vw, 46px);
      line-height:1;
    }
    .wow-trust-panel p{
      max-width:720px;
      margin:10px 0 0;
      color:#596275;
      font-size:15px;
      line-height:1.55;
      font-family:'Manrope', system-ui, sans-serif;
    }
    @media (max-width: 980px){
      .wow-needs-hero,
      .wow-needs-grid,
      .wow-quick-browse{
        grid-template-columns:1fr;
      }
      .wow-hero-note{ max-width:520px; }
      .wow-quick-browse{ grid-template-columns:repeat(2, minmax(0,1fr)); }
    }
    @media (max-width: 760px){
      .wow-needs-page{ padding:42px 0 58px; }
      .wow-need-card__footer,
      .wow-trust-panel{
        align-items:flex-start;
        flex-direction:column;
      }
    }
    @media (max-width: 560px){
      .wow-needs-container,
      .wow-page-grid{
        width:min(100% - 28px, 1280px);
      }
      .wow-needs-hero h1{ font-size:50px; }
      .wow-needs-hero p{ font-size:16px; }
      .wow-need-card__inner,
      .wow-need-card__footer,
      .wow-trust-panel{ padding-left:18px; padding-right:18px; }
      .wow-needs-grid,
      .wow-quick-browse{ grid-template-columns:1fr; }
      .wow-needs-page .btn-wow{ width:100%; }
    }
  </style>
@endpush

@section('content')
@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Needs'],
  ],
  'schemaUrl' => url('/needs'),
])

<main class="wow-needs-page">
  <div class="wow-page-grid" aria-hidden="true"></div>

  <div class="wow-needs-container">
    @include('partials.landing-hero', [
      'heroEyebrow' => 'Browse',
      'heroTitle' => 'By Need',
      'heroIntro' => 'Start with what you need most, then we’ll match you with therapies, classes and experiences that fit.',
      'heroAsideLabel' => 'Simple, calm, useful',
      'heroAsideTitle' => 'A clearer place to start',
      'heroAsideText' => 'No clutter. Just clear pathways into the right support.',
    ])

    <section class="wow-needs-grid" aria-label="Need modalities">
      @foreach(($needs ?? []) as $need)
        <article class="wow-need-card">
          @if(!empty($need['image_url']))<img src="{{ $need['image_url'] }}" alt="{{ $need['name'] }}" loading="lazy" style="width:100%;height:160px;object-fit:cover">@endif
          <div class="wow-need-card__inner">
            <h2>{{ $need['name'] }}</h2>
            @if(!empty($need['description']))
              <p>{{ $need['description'] }}</p>
            @endif
          </div>
          <footer class="wow-need-card__footer">
            <small>Browse therapies and experiences for {{ $need['name'] }}.</small>
            <a href="{{ route('needs.show', ['slug' => $need['slug']]) }}" class="btn-wow btn-wow--primary">View {{ $need['name'] }}</a>
          </footer>
        </article>
      @endforeach
    </section>

    <section class="wow-quick-browse" aria-label="Quick browse links">
      <a href="{{ url('/therapies') }}" class="wow-quick-card">
        <div>
          <h3>Therapies</h3>
          <p>Explore massage, reiki, breathwork, coaching, yoga and more.</p>
        </div>
        <span>Browse therapies →</span>
      </a>

      <a href="{{ url('/feel') }}" class="wow-quick-card">
        <div>
          <h3>By Need</h3>
          <p>Find support for stress, sleep, pain, energy and emotional wellbeing.</p>
        </div>
        <span>Browse by need →</span>
      </a>

      <a href="{{ url('/events') }}" class="wow-quick-card">
        <div>
          <h3>Events</h3>
          <p>Discover wellness events, workshops, retreats and classes.</p>
        </div>
        <span>Browse events →</span>
      </a>

      <a href="{{ url('/search?mode=online&max_price=50') }}" class="wow-quick-card">
        <div>
          <h3>Under £50</h3>
          <p>Start with lower-cost online sessions and accessible options.</p>
        </div>
        <span>Browse under £50 →</span>
      </a>
    </section>

    <section class="wow-trust-panel">
      <div>
        <p class="wow-kicker">Before you book</p>
        <h2>Wellness, with care</h2>
        <p>Always check suitability, practitioner details and any contraindications before booking. If you are unsure whether a therapy is right for you, speak to the practitioner first.</p>
      </div>

      <a href="{{ url('/safety-and-contraindications') }}" class="btn-wow btn-wow--soft">Read safety guidance</a>
    </section>
  </div>
</main>
@endsection
