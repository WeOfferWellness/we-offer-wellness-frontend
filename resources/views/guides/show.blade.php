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
    .guide-page{padding:34px 0 86px;background:
      radial-gradient(circle at top left, rgba(228,245,238,.9), transparent 36%),
      linear-gradient(180deg,#f6fbf8 0%,#ffffff 30%,#ffffff 100%)}
    .guide-wrap{max-width:1180px;margin:0 auto;padding:0 18px}
    .guide-top{display:grid;gap:22px;grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);margin-top:18px}
    .guide-box{background:#fff;border:1px solid #e6ece8;border-radius:28px;box-shadow:0 24px 60px rgba(16,24,40,.08)}
    .guide-hero{padding:34px}
    .guide-kicker{margin:0 0 10px;font-size:12px;letter-spacing:.18em;text-transform:uppercase;color:#4b635c;font-weight:800}
    .guide-hero h1,.guide-section h2,.guide-rail h3,.guide-card h3{margin:0;color:#0d1d18;font-family:"Playfair Display",serif;letter-spacing:-.05em}
    .guide-hero h1{font-size:clamp(40px,5.6vw,74px);line-height:.92;max-width:12ch}
    .guide-hero p{margin:18px 0 0;color:#53655f;font-size:17px;line-height:1.72;max-width:66ch}
    .guide-answer{padding:28px;background:linear-gradient(180deg,#f2faf6,#ffffff)}
    .guide-answer small{display:block;color:#4b635c;text-transform:uppercase;letter-spacing:.16em;font-weight:800}
    .guide-answer p{margin:12px 0 0;color:#103227;font-size:18px;line-height:1.7}
    .guide-grid{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:24px;margin-top:30px}
    .guide-main{display:grid;gap:18px}
    .guide-section{padding:26px}
    .guide-section h2{font-size:32px;line-height:1}
    .guide-section p{margin:14px 0 0;color:#546660;line-height:1.75}
    .guide-toc-list,.guide-link-list,.guide-faq-list{display:grid;gap:12px;margin-top:18px}
    .guide-anchor,.guide-side-link,.guide-faq{display:block;padding:14px 16px;border:1px solid #e6ece8;border-radius:18px;background:#fbfdfc;text-decoration:none}
    .guide-anchor strong,.guide-side-link strong,.guide-faq strong{display:block;color:#11231d;font-size:15px}
    .guide-anchor span,.guide-side-link span,.guide-faq span{display:block;margin-top:4px;color:#60716b;font-size:13px;line-height:1.55}
    .guide-rail{display:grid;gap:18px;align-content:start}
    .guide-rail-card{padding:22px}
    .guide-rail-card p{margin:12px 0 0;color:#5e7069;line-height:1.65}
    .guide-offering{display:block;padding:16px;border:1px solid #e6ece8;border-radius:18px;background:#fff;text-decoration:none}
    .guide-offering + .guide-offering{margin-top:12px}
    .guide-offering strong{display:block;color:#10231d;font-size:15px}
    .guide-offering span{display:block;margin-top:5px;color:#5f716b;font-size:13px;line-height:1.55}
    .guide-offering em{display:block;margin-top:8px;color:#0f6b57;font-style:normal;font-weight:700}
    .guide-cta{padding:26px;background:linear-gradient(135deg,#0f6b57,#153b33);border-color:#153b33}
    .guide-cta h3,.guide-cta p,.guide-cta a{color:#fff}
    .guide-cta-links{display:grid;gap:10px;margin-top:16px}
    .guide-cta-link{display:block;padding:14px 16px;border-radius:16px;background:rgba(255,255,255,.1);text-decoration:none;border:1px solid rgba(255,255,255,.14)}
    .guide-chip-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
    .guide-chip{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;background:#eef8f3;color:#0f6b57;font-size:13px;font-weight:800;text-decoration:none}
    @media (max-width: 991px){
      .guide-top,.guide-grid{grid-template-columns:1fr}
    }
  </style>
@endpush

@section('content')
  <section class="guide-page">
    <div class="guide-wrap">
      @include('partials.breadcrumbs', ['crumbs' => $page['breadcrumbs'] ?? [], 'schemaEnabled' => false])

      @include('partials.landing-hero', [
        'heroEyebrow' => 'We Offer Wellness® Guide',
        'heroTitle' => $page['h1'],
        'heroIntro' => $page['intro'],
        'heroChips' => [
          ['label' => \Illuminate\Support\Str::headline($page['format'] ?? 'therapies'), 'href' => url('/'.($page['format'] ?? 'therapies'))],
          ['label' => $page['modality_label'], 'href' => url('/'.($page['format'] ?? 'therapies').'/'.($page['modality'] ?? ''))],
          ['label' => 'Online options', 'href' => url('/online/'.($page['modality'] ?? ''))],
        ],
        'heroAsideLabel' => 'Quick answer',
        'heroAsideTitle' => 'Start with what matters',
        'heroAsideText' => $page['quick_answer'],
      ])

      <div class="guide-grid">
        <div class="guide-main">
          <div class="guide-box guide-section">
            <h2>Table of contents</h2>
            <div class="guide-toc-list">
              @foreach(($page['sections'] ?? []) as $section)
                <a class="guide-anchor" href="#{{ $section['id'] }}">
                  <strong>{{ $section['heading'] }}</strong>
                </a>
              @endforeach
            </div>
          </div>

          @foreach(($page['sections'] ?? []) as $section)
            <div class="guide-box guide-section" id="{{ $section['id'] }}">
              <h2>{{ $section['heading'] }}</h2>
              @foreach(($section['paragraphs'] ?? []) as $paragraph)
                <p>{{ $paragraph }}</p>
              @endforeach
            </div>
          @endforeach

          <div class="guide-box guide-section">
            <h2>Safety and suitability note</h2>
            <p>{{ $page['safety_note'] ?? '' }}</p>
          </div>

          @if(!empty($page['related_guides']))
            <div class="guide-box guide-section">
              <h2>Related guides</h2>
              <div class="guide-link-list">
                @foreach(($page['related_guides'] ?? []) as $item)
                  <a class="guide-side-link" href="{{ $item['url'] }}">
                    <strong>{{ $item['title'] }}</strong>
                    @if(!empty($item['summary']))<span>{{ $item['summary'] }}</span>@endif
                  </a>
                @endforeach
              </div>
            </div>
          @endif

          @if(!empty($page['faqs']))
            <div class="guide-box guide-section">
              <h2>FAQs</h2>
              <div class="guide-faq-list">
                @foreach(($page['faqs'] ?? []) as $faq)
                  <div class="guide-faq">
                    <strong>{{ $faq['q'] }}</strong>
                    <span>{{ $faq['a'] }}</span>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        </div>

        <aside class="guide-rail">
          <div class="guide-box guide-rail-card">
            <h3>Related offerings</h3>
            <p>Browse live listings connected to this guide topic.</p>
            @forelse(($page['offerings'] ?? []) as $item)
              <a class="guide-offering" href="{{ $item['url'] }}">
                <strong>{{ $item['title'] }}</strong>
                @if(!empty($item['summary']))<span>{{ $item['summary'] }}</span>@endif
                @if(!empty($item['practitioner']))<em>{{ $item['practitioner'] }}</em>@endif
              </a>
            @empty
              <a class="guide-offering" href="{{ url('/' . ($page['format'] ?? 'therapies') . '/' . ($page['modality'] ?? '')) }}">
                <strong>Browse related offerings</strong>
                <span>See current listings for this modality.</span>
              </a>
            @endforelse
          </div>

          <div class="guide-box guide-rail-card">
            <h3>Online options</h3>
            <p>Explore live and virtual sessions where available.</p>
            @foreach(($page['online_links'] ?? []) as $item)
              <a class="guide-offering" href="{{ $item['url'] }}">
                <strong>{{ $item['label'] }}</strong>
              </a>
            @endforeach
          </div>

          <div class="guide-box guide-rail-card">
            <h3>Find near you</h3>
            <p>Start broad, then narrow down to county and town pages.</p>
            @foreach(($page['nearby_links'] ?? []) as $item)
              <a class="guide-offering" href="{{ $item['url'] }}">
                <strong>{{ $item['label'] }}</strong>
              </a>
            @endforeach
          </div>

          @if(!empty($page['practitioners']))
            <div class="guide-box guide-rail-card">
              <h3>Practitioner profiles</h3>
              <p>Meet trusted practitioners connected to live offerings.</p>
              @foreach(($page['practitioners'] ?? []) as $item)
                <a class="guide-offering" href="{{ $item['url'] }}">
                  <strong>{{ $item['name'] }}</strong>
                </a>
              @endforeach
            </div>
          @endif

          <div class="guide-box guide-cta guide-rail-card">
            <h3>{{ data_get($page, 'final_cta.heading') }}</h3>
            <p>{{ data_get($page, 'final_cta.text') }}</p>
            <div class="guide-cta-links">
              @foreach((array) data_get($page, 'final_cta.links', []) as $item)
                <a class="guide-cta-link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
              @endforeach
            </div>
          </div>
        </aside>
      </div>
    </div>
  </section>
@endsection
