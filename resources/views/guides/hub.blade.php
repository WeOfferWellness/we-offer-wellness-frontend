@extends('layouts.app')

@php($seo = $page['seo'] ?? [])

@push('head')
  <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}">
  @if(!empty($page['schema']))
    <script type="application/ld+json">{!! json_encode($page['schema'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}</script>
  @endif
@endpush

@push('styles')
  <style>
    .guide-hub{padding:34px 0 80px;background:linear-gradient(180deg,#f5fbf8 0%,#fff 32%,#fff 100%)}
    .guide-shell{max-width:1180px;margin:0 auto;padding:0 18px}
    .guide-hero{display:grid;gap:22px;grid-template-columns:minmax(0,1.25fr) minmax(320px,.9fr);margin-top:18px}
    .guide-panel{background:#fff;border:1px solid #e6ece8;border-radius:28px;box-shadow:0 24px 60px rgba(16,24,40,.08)}
    .guide-hero-copy{padding:34px}
    .guide-kicker{margin:0 0 10px;font-size:12px;letter-spacing:.18em;text-transform:uppercase;color:#4b635c;font-weight:800}
    .guide-hero-copy h1,.guide-card h3,.guide-links h2,.guide-offerings h2{margin:0;color:#0d1d18;font-family:"Playfair Display",serif;letter-spacing:-.05em}
    .guide-hero-copy h1{font-size:clamp(40px,5.4vw,72px);line-height:.92;max-width:10ch}
    .guide-hero-copy p{margin:18px 0 0;color:#51635d;font-size:17px;line-height:1.7;max-width:64ch}
    .guide-stat{padding:28px;background:linear-gradient(180deg,#f4fbf8,#ffffff);display:flex;flex-direction:column;justify-content:space-between}
    .guide-stat strong{display:block;font-size:13px;letter-spacing:.18em;text-transform:uppercase;color:#4b635c}
    .guide-stat-number{margin-top:18px;font-family:"Playfair Display",serif;font-size:clamp(48px,5vw,74px);line-height:.9;color:#0f6b57}
    .guide-stat p{margin:12px 0 0;color:#51635d;line-height:1.65}
    .guide-block{margin-top:30px}
    .guide-grid{display:grid;gap:18px;grid-template-columns:repeat(3,minmax(0,1fr))}
    .guide-card{padding:24px}
    .guide-card h3{font-size:28px;line-height:1}
    .guide-card p{margin:10px 0 0;color:#60716b;line-height:1.65}
    .guide-list{display:grid;gap:12px;margin-top:18px}
    .guide-link{display:block;padding:16px 18px;border:1px solid #e6ece8;border-radius:18px;background:#fbfdfc;text-decoration:none}
    .guide-link strong{display:block;color:#10231d;font-size:15px}
    .guide-link span{display:block;margin-top:4px;color:#60716b;font-size:13px;line-height:1.5}
    .guide-link:hover{border-color:#bcd6cd;transform:translateY(-1px)}
    .guide-group{margin-top:18px;padding:18px;border:1px solid #eef2f0;border-radius:22px;background:#fff}
    .guide-group + .guide-group{margin-top:14px}
    .guide-group h3{margin:0 0 10px;color:#11231d;font-size:20px}
    .guide-group-links{display:grid;gap:10px}
    .guide-offering-grid{display:grid;gap:14px;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:18px}
    .guide-offering{padding:18px;border:1px solid #e6ece8;border-radius:20px;background:#fff}
    .guide-offering strong{display:block;color:#11231d;font-size:15px}
    .guide-offering p{margin:8px 0 0;color:#60716b;font-size:14px;line-height:1.6}
    .guide-offering small{display:block;margin-top:10px;color:#0f6b57;font-weight:700}
    @media (max-width: 991px){
      .guide-hero,.guide-grid,.guide-offering-grid{grid-template-columns:1fr}
    }
  </style>
@endpush

@section('content')
  <section class="guide-hub">
    <div class="guide-shell">
      @include('partials.breadcrumbs', ['crumbs' => $page['breadcrumbs'] ?? [], 'schemaEnabled' => false])

      @include('partials.landing-hero', [
        'heroEyebrow' => 'We Offer Wellness® Guides',
        'heroTitle' => $page['h1'] ?? 'Guides',
        'heroIntro' => $page['intro'] ?? '',
        'heroAsideLabel' => 'Guide library',
        'heroAsideTitle' => count($page['popular_guides'] ?? []).' ways to begin',
        'heroAsideText' => 'Useful landing pages linking education into real discovery, nearby browsing and live offerings.',
      ])
      @include('partials.hero-meta', [
        'items' => array_values(array_filter([
          count($page['popular_guides'] ?? []).' popular guides',
          count($page['what_is_guides'] ?? []).' explainers',
          'Practical wellness guidance',
        ])),
      ])

      <div class="guide-block guide-grid">
        <div class="guide-panel guide-card">
          <h3>Popular guides</h3>
          <p>Core entry points for people exploring modalities, support needs and what to expect.</p>
          <div class="guide-list">
            @foreach(($page['popular_guides'] ?? []) as $item)
              <a class="guide-link" href="{{ $item['url'] }}">
                <strong>{{ $item['label'] }}</strong>
                @if(!empty($item['summary']))<span>{{ $item['summary'] }}</span>@endif
              </a>
            @endforeach
          </div>
        </div>

        <div class="guide-panel guide-card">
          <h3>What is guides</h3>
          <p>Clear introductions to major wellness modalities and how people usually experience them.</p>
          <div class="guide-list">
            @foreach(($page['what_is_guides'] ?? []) as $item)
              <a class="guide-link" href="{{ $item['url'] }}">
                <strong>{{ $item['label'] }}</strong>
                @if(!empty($item['summary']))<span>{{ $item['summary'] }}</span>@endif
              </a>
            @endforeach
          </div>
        </div>

        <div class="guide-panel guide-card">
          <h3>Guides by format</h3>
          <p>Browse educational pages within therapies, classes and events.</p>
          <div class="guide-group-links">
            @foreach(($page['guides_by_format'] ?? []) as $group)
              <a class="guide-link" href="{{ $group['url'] }}">
                <strong>{{ $group['title'] }}</strong>
                <span>{{ count($group['items'] ?? []) }} guide pages</span>
              </a>
            @endforeach
          </div>
        </div>
      </div>

      @if(!empty($page['guides_by_need']))
        <div class="guide-block guide-panel guide-card guide-links">
          <h2>Guides by need</h2>
          @foreach(($page['guides_by_need'] ?? []) as $group)
            <div class="guide-group">
              <h3>
                @if(!empty($group['url']))
                  <a href="{{ $group['url'] }}">{{ $group['title'] }}</a>
                @else
                  {{ $group['title'] }}
                @endif
              </h3>
              <div class="guide-group-links">
                @foreach(($group['items'] ?? []) as $item)
                  <a class="guide-link" href="{{ $item['url'] }}">
                    <strong>{{ $item['label'] }}</strong>
                    @if(!empty($item['summary']))<span>{{ $item['summary'] }}</span>@endif
                  </a>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      @endif

      @if(!empty($page['guides_by_modality']))
        <div class="guide-block guide-panel guide-card guide-links">
          <h2>Guides by modality</h2>
          @foreach(($page['guides_by_modality'] ?? []) as $group)
            <div class="guide-group">
              <h3><a href="{{ $group['url'] }}">{{ $group['title'] }}</a></h3>
              <div class="guide-group-links">
                @foreach(($group['items'] ?? []) as $item)
                  <a class="guide-link" href="{{ $item['url'] }}">
                    <strong>{{ $item['label'] }}</strong>
                    @if(!empty($item['summary']))<span>{{ $item['summary'] }}</span>@endif
                  </a>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      @endif

      @if(!empty($page['featured_offerings']))
        <div class="guide-block guide-panel guide-card guide-offerings">
          <h2>Featured offerings</h2>
          <div class="guide-offering-grid">
            @foreach(($page['featured_offerings'] ?? []) as $item)
              <a class="guide-offering" href="{{ $item['url'] }}">
                <strong>{{ $item['title'] }}</strong>
                @if(!empty($item['summary']))<p>{{ $item['summary'] }}</p>@endif
                @if(!empty($item['practitioner']))<small>{{ $item['practitioner'] }}</small>@endif
              </a>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </section>
@endsection
