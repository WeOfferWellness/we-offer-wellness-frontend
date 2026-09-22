@extends('layouts.app')

@section('title', 'Offerings | We Offer Wellness™')

@push('styles')
<style>
  .offerings-page{
    position:relative;
    overflow:hidden;
    padding:56px 0 76px;
  }
  .offerings-shell{
    position:relative;
    z-index:1;
    display:grid;
    gap:28px;
  }
  .offerings-hero{
    display:grid;
    grid-template-columns:minmax(0,1.12fr) minmax(320px,.88fr);
    gap:22px;
    border:1px solid rgba(16,24,40,.10);
    border-radius:22px;
    background:rgba(255,255,255,.98);
    box-shadow:0 22px 60px rgba(16,24,40,.08);
    overflow:hidden;
  }
  .offerings-hero__copy{ padding:30px; }
  .offerings-kicker{
    margin:0 0 10px;
    color:#344054;
    font-size:13px;
    font-weight:700;
    letter-spacing:.16em;
    text-transform:uppercase;
  }
  .offerings-hero h1{
    margin:0;
    color:#101828;
    font-family:"Playfair Display", Georgia, "Times New Roman", serif;
    font-size:clamp(42px, 5vw, 72px);
    line-height:.95;
    letter-spacing:-.06em;
  }
  .offerings-hero p{
    margin:16px 0 0;
    color:#596275;
    font-size:16px;
    line-height:1.6;
    max-width:60ch;
  }
  .offerings-actions{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:24px;
  }
  .offerings-panel{
    padding:22px;
    border-left:1px solid #edf0f2;
    background:#f8fafc;
    display:grid;
    align-content:center;
    gap:12px;
  }
  .offerings-panel__item{
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:14px 16px;
    border:1px solid rgba(16,24,40,.08);
    border-radius:16px;
    background:#fff;
    box-shadow:0 10px 28px rgba(16,24,40,.04);
  }
  .offerings-panel__icon{
    width:30px;
    height:30px;
    border-radius:999px;
    display:grid;
    place-items:center;
    flex:0 0 30px;
    background:#e8f5f1;
    color:#4f9381;
    font-weight:800;
  }
  .offerings-panel__item strong{
    display:block;
    font-size:14px;
    line-height:1.2;
    color:#101828;
  }
  .offerings-panel__item span{
    display:block;
    margin-top:4px;
    color:#667085;
    font-size:13px;
    line-height:1.45;
  }
  .offerings-block{
    display:grid;
    gap:16px;
  }
  .offerings-head{
    display:flex;
    justify-content:space-between;
    align-items:end;
    gap:12px;
  }
  .offerings-head h2{
    margin:0;
    color:#101828;
    font-family:"Playfair Display", Georgia, "Times New Roman", serif;
    font-size:clamp(30px, 4vw, 48px);
    line-height:1;
    letter-spacing:-.05em;
  }
  .offerings-head p{
    margin:0;
    color:#667085;
    font-size:14px;
  }
  .offerings-grid{
    display:grid;
    gap:16px;
    grid-template-columns:repeat(4, minmax(0, 1fr));
  }
  .offerings-card{
    display:grid;
    gap:10px;
    padding:18px;
    border:1px solid rgba(16,24,40,.10);
    border-radius:18px;
    background:#fff;
    box-shadow:0 14px 34px rgba(16,24,40,.05);
    text-decoration:none;
    color:inherit;
    transition:transform 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
  }
  .offerings-card:hover{
    transform:translateY(-2px);
    border-color:rgba(79,147,129,.35);
    box-shadow:0 20px 42px rgba(16,24,40,.08);
  }
  .offerings-card__pill{
    width:42px;
    height:42px;
    border-radius:12px;
    display:grid;
    place-items:center;
    background:#e8f5f1;
    color:#4f9381;
    font-weight:800;
  }
  .offerings-card h3{
    margin:0;
    color:#101828;
    font-size:17px;
    line-height:1.2;
    letter-spacing:-.03em;
  }
  .offerings-card p{
    margin:0;
    color:#667085;
    font-size:13px;
    line-height:1.55;
  }
  .offerings-card__foot{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    margin-top:2px;
    padding-top:12px;
    border-top:1px solid #edf0f2;
    color:#667085;
    font-size:13px;
  }
  .offerings-card__foot strong{ color:#101828; }
  .offerings-location{
    display:grid;
    gap:8px;
  }
  .offerings-location__subtitle{
    color:#667085;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.08em;
  }
  .offerings-location__title{
    color:#101828;
    font-size:16px;
    font-weight:700;
  }
  .offerings-location__copy{
    color:#667085;
    font-size:13px;
    line-height:1.5;
  }
  .offerings-empty{
    padding:22px;
    border:1px dashed rgba(16,24,40,.18);
    border-radius:16px;
    color:#667085;
    background:#fafafa;
  }
  @media (max-width: 1100px){
    .offerings-grid{ grid-template-columns:repeat(3, minmax(0, 1fr)); }
  }
  @media (max-width: 980px){
    .offerings-hero{ grid-template-columns:1fr; }
    .offerings-panel{ border-left:0; border-top:1px solid #edf0f2; }
    .offerings-grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); }
  }
  @media (max-width: 640px){
    .offerings-page{ padding:40px 0 60px; }
    .offerings-hero__copy,
    .offerings-panel{ padding:20px; }
    .offerings-grid{ grid-template-columns:1fr; }
  }
</style>
@endpush

@section('content')
@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Offerings'],
  ],
  'schemaUrl' => url('/offerings'),
  'chips' => array_filter([
    isset($categories) ? count($categories) . ' modalities' : null,
    'Live catalogue',
  ]),
])

<section class="offerings-page">
  <div class="container-page offerings-shell">
    <div class="offerings-hero">
      <div class="offerings-hero__copy">
        <p class="offerings-kicker">Offerings</p>
        <h1>Find the right path by modality, type, location or pain point.</h1>
        <p>This is the new hub for browsing live offerings. Use it to move through the collection the way real people shop: by what it is, where it is, and how they feel.</p>
        <div class="offerings-actions">
          <a href="/search" class="btn-wow btn-wow--primary btn-sm btn-arrow" data-loader-init="1">
            <span class="btn-label">Search everything</span>
            <span class="btn-icon-wrap" aria-hidden="true">
              <svg class="btn-icon-hover" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"></path></svg>
              <svg class="btn-icon-default" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12l-4 4m4-4-4-4"></path></svg>
            </span>
          </a>
          <a href="/giftcards" class="btn-wow btn-wow--outline btn-sm btn-arrow" data-loader-init="1">
            <span class="btn-label">Gift cards</span>
            <span class="btn-icon-wrap" aria-hidden="true">
              <svg class="btn-icon-hover" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"></path></svg>
              <svg class="btn-icon-default" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12l-4 4m4-4-4-4"></path></svg>
            </span>
          </a>
        </div>
      </div>
      <div class="offerings-panel">
        <div class="offerings-panel__item">
          <div class="offerings-panel__icon">A</div>
          <div>
            <strong>Modalities</strong>
            <span>Browse the live catalogue by modality and find the largest collections first.</span>
          </div>
        </div>
        <div class="offerings-panel__item">
          <div class="offerings-panel__icon">B</div>
          <div>
            <strong>Types</strong>
            <span>Jump straight into therapies, classes, workshops, events, retreats or gifts.</span>
          </div>
        </div>
        <div class="offerings-panel__item">
          <div class="offerings-panel__icon">C</div>
          <div>
            <strong>Pain points</strong>
            <span>Search by how you feel, not just by what the listing is called.</span>
          </div>
        </div>
      </div>
    </div>

    <div class="offerings-block">
      <div class="offerings-head">
        <div>
          <p class="offerings-kicker">Modalities</p>
          <h2>Start with the biggest areas</h2>
        </div>
        <p>{{ count($categories) }} modalities with live counts</p>
      </div>
      @if (empty($categories))
        <div class="offerings-empty">No modalities available right now.</div>
      @else
        <div class="offerings-grid">
          @foreach ($categories as $category)
            <a class="offerings-card" href="{{ $category['path'] }}">
              <div class="offerings-card__pill">{{ strtoupper(substr($category['title'], 0, 1)) }}</div>
              <h3>{{ $category['title'] }}</h3>
              <p>{{ $category['tagline'] ?: 'Browse live offerings in this modality.' }}</p>
              <div class="offerings-card__foot">
                <span>Modality</span>
                <strong>{{ $category['count'] }} offerings</strong>
              </div>
            </a>
          @endforeach
        </div>
      @endif
    </div>

    <div class="offerings-block">
      <div class="offerings-head">
        <div>
          <p class="offerings-kicker">Types</p>
          <h2>Choose the format</h2>
        </div>
      </div>
      <div class="offerings-grid">
        @foreach ($types as $type)
          <a class="offerings-card" href="{{ $type['path'] }}">
            <div class="offerings-card__pill">{{ strtoupper(substr($type['title'], 0, 1)) }}</div>
            <h3>{{ $type['title'] }}</h3>
            <p>{{ $type['copy'] }}</p>
            <div class="offerings-card__foot">
              <span>Type</span>
              <strong>Open</strong>
            </div>
          </a>
        @endforeach
      </div>
    </div>

    <div class="offerings-block">
      <div class="offerings-head">
        <div>
          <p class="offerings-kicker">Locations</p>
          <h2>Browse by place</h2>
        </div>
      </div>
      @if (empty($locations))
        <div class="offerings-empty">No locations available right now.</div>
      @else
        <div class="offerings-grid">
          @foreach ($locations as $location)
            <a class="offerings-card" href="{{ $location['path'] }}">
              <div class="offerings-location">
                <span class="offerings-location__subtitle">{{ $location['online'] ? 'Virtual' : 'Location' }}</span>
                <div class="offerings-location__title">{{ $location['title'] }}</div>
                @if (!empty($location['subtitle']))
                  <div class="offerings-location__copy">{{ $location['subtitle'] }}</div>
                @endif
              </div>
              <div class="offerings-card__foot">
                <span>Location</span>
                <strong>Open</strong>
              </div>
            </a>
          @endforeach
        </div>
      @endif
    </div>

    <div class="offerings-block">
      <div class="offerings-head">
        <div>
          <p class="offerings-kicker">How are you feeling?</p>
          <h2>Find support by pain point</h2>
        </div>
      </div>
      <div class="offerings-grid">
        @foreach ($painpoints as $painpoint)
          <a class="offerings-card" href="/needs/{{ $painpoint['slug'] }}">
            <div class="offerings-card__pill">{{ strtoupper(substr($painpoint['title'], 0, 1)) }}</div>
            <h3>{{ $painpoint['title'] }}</h3>
            <p>{{ $painpoint['copy'] }}</p>
            <div class="offerings-card__foot">
              <span>Pain point</span>
              <strong>Explore</strong>
            </div>
          </a>
        @endforeach
      </div>
    </div>
  </div>
</section>
@endsection
