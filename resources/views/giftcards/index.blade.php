@extends('layouts.app')

@section('title', 'Gift Cards | We Offer Wellness™')
@section('body-class', 'giftcards-body')

@push('styles')
<style>
  .giftcards-page{
    position:relative;
    overflow:hidden;
    padding:0 0 76px;
    background:transparent;
  }
  body.giftcards-body,
  body.giftcards-body main,
  body.giftcards-body .text-ink-800{
    background:transparent !important;
  }
  body.giftcards-body::before,
  body.giftcards-body::after{
    content:none !important;
    display:none !important;
    background:none !important;
  }
  .giftcards-shell{
    position:relative;
    z-index:1;
    display:grid;
    gap:28px;
  }
  .giftcards-hero{
    display:grid;
    grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr);
    gap:22px;
    align-items:stretch;
    border:1px solid rgba(16,24,40,.10);
    border-radius:22px;
    background:rgba(255,255,255,.98);
    box-shadow:0 22px 60px rgba(16,24,40,.08);
    overflow:hidden;
  }
  .giftcards-hero__copy{
    padding:30px;
  }
  .giftcards-kicker{
    margin:0 0 10px;
    color:#344054;
    font-size:13px;
    font-weight:700;
    letter-spacing:.16em;
    text-transform:uppercase;
  }
  .giftcards-hero h1{
    margin:0;
    color:#101828;
    font-family:"Playfair Display", Georgia, "Times New Roman", serif;
    font-size:clamp(42px, 5vw, 72px);
    line-height:.95;
    letter-spacing:-.06em;
  }
  .giftcards-hero p{
    margin:16px 0 0;
    color:#596275;
    font-size:16px;
    line-height:1.6;
    max-width:58ch;
  }
  .giftcards-actions{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:24px;
  }
  .giftcards-proof{
    padding:22px;
    border-left:1px solid #edf0f2;
    background:#f8fafc;
    display:grid;
    align-content:center;
    gap:12px;
  }
  .giftcards-proof__item{
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:14px 16px;
    border:1px solid rgba(16,24,40,.08);
    border-radius:16px;
    background:#fff;
    box-shadow:0 10px 28px rgba(16,24,40,.04);
  }
  .giftcards-proof__icon{
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
  .giftcards-proof__item strong{
    display:block;
    font-size:14px;
    line-height:1.2;
    color:#101828;
  }
  .giftcards-proof__item span{
    display:block;
    margin-top:4px;
    color:#667085;
    font-size:13px;
    line-height:1.45;
  }
  .giftcards-section-title{
    display:flex;
    justify-content:space-between;
    align-items:end;
    gap:12px;
    margin:33px 0 0;
  }
  .giftcards-section-title h2{
    margin:0;
    color:#101828;
    font-family:"Playfair Display", Georgia, "Times New Roman", serif;
    font-size:clamp(30px, 4vw, 48px);
    line-height:1;
    letter-spacing:-.05em;
  }
  .giftcards-section-title p{
    margin:0;
    color:#667085;
    font-size:14px;
  }
  .giftcards-list{
    display:grid;
    gap:18px;
  }
  .giftcard-card{
    display:grid;
    grid-template-columns:minmax(260px,.9fr) minmax(0,1.1fr);
    gap:0;
    border:1px solid rgba(16,24,40,.12);
    border-radius:20px;
    overflow:hidden;
    background:#fff;
    box-shadow:0 18px 48px rgba(16,24,40,.08);
  }
  .giftcard-card__media{
    position:relative;
    min-height:100%;
    background:linear-gradient(135deg,#e8f5f1,#f8fafc 50%,#eef2f6);
    padding:18px;
  }
  .giftcard-card__media img{
    width:100%;
    height:100%;
    min-height:260px;
    object-fit:cover;
    border-radius:14px;
    display:block;
    border:1px solid rgba(16,24,40,.10);
    box-shadow:0 18px 36px rgba(16,24,40,.08);
  }
  .giftcard-card__body{
    padding:24px;
  }
  .giftcard-card__body h3{
    margin:0;
    color:#101828;
    font-family:"Playfair Display", Georgia, "Times New Roman", serif;
    font-size:clamp(30px, 3vw, 42px);
    line-height:1;
    letter-spacing:-.05em;
  }
  .giftcard-card__body p{
    margin:14px 0 0;
    color:#596275;
    font-size:15px;
    line-height:1.6;
  }
  .giftcard-badges{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-top:18px;
  }
  .giftcard-badge{
    min-height:30px;
    display:inline-flex;
    align-items:center;
    padding:0 10px;
    border-radius:999px;
    border:1px solid #dfe4ea;
    background:#fff;
    color:#344054;
    font-size:12px;
    font-weight:700;
  }
  .giftcard-amounts{
    margin-top:22px;
    display:grid;
    gap:12px;
  }
  .giftcard-amount{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    gap:14px;
    align-items:center;
    padding:14px 16px;
    border:1px solid rgba(16,24,40,.10);
    border-radius:16px;
    background:#f8fafc;
  }
  .giftcard-amount strong{
    display:block;
    color:#101828;
    font-size:18px;
    line-height:1.15;
  }
  .giftcard-amount span{
    display:block;
    margin-top:4px;
    color:#667085;
    font-size:13px;
  }
  .giftcard-amount__actions{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    justify-content:flex-end;
  }
  .giftcard-amount__actions .btn-wow{
    min-width:140px;
    justify-content:center;
  }
  .giftcards-note{
    margin:0;
    color:#667085;
    font-size:13px;
    line-height:1.55;
  }
  .giftcards-empty{
    padding:22px;
    border:1px dashed rgba(16,24,40,.18);
    border-radius:16px;
    color:#667085;
    background:#fafafa;
  }
  @media (max-width: 980px){
    .giftcards-hero,
    .giftcard-card{ grid-template-columns:1fr; }
    .giftcards-proof{ border-left:0; border-top:1px solid #edf0f2; }
  }
  @media (max-width: 640px){
    .giftcards-page{ padding:0 0 60px; }
    .giftcards-hero__copy,
    .giftcards-proof,
    .giftcard-card__body{ padding:20px; }
    .giftcard-card__media{ padding:14px; }
    .giftcard-amount{ grid-template-columns:1fr; }
    .giftcard-amount__actions{ justify-content:flex-start; }
  }
</style>
@endpush

@section('content')
@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Gift Cards'],
  ],
  'schemaUrl' => url('/giftcards'),
])

@include('partials.landing-hero', [
  'heroEyebrow' => 'Gift cards',
  'heroTitle' => 'Give wellness, your way',
  'heroIntro' => 'Choose a digital gift card with flexible denominations, instant email delivery and wellness experiences to suit the person you are celebrating.',
  'heroActions' => [
    ['label' => 'How gifting works', 'href' => '/help/gift-cards', 'style' => 'outline'],
    ['label' => 'Browse gifting ideas', 'href' => '/search?type=gifts'],
  ],
  'heroAsideLabel' => 'A thoughtful way to give',
  'heroAsideTitle' => 'More choice, less guesswork',
  'heroAsideText' => 'Let them choose the experience, format and amount that feels right for them.',
])

@include('partials.hero-meta', [
  'items' => [
    ['label' => 'Digital gift cards', 'strong' => true],
    'Flexible denominations',
    'Instant email delivery',
  ],
])

<section class="giftcards-page">
  <div class="container-page giftcards-shell">
    <div class="giftcards-section-title">
      <div>
        <p class="giftcards-kicker">Available gift cards</p>
        <h2>Pick the amount that feels right</h2>
      </div>
      <p>{{ count($giftCards) }} gift card{{ count($giftCards) === 1 ? '' : 's' }} found in the database</p>
    </div>

    @if (empty($giftCards))
      <div class="giftcards-empty">
        No gift cards with denominations were found.
      </div>
    @else
      <div class="giftcards-list">
        @foreach ($giftCards as $giftCard)
          <article class="giftcard-card">
            <div class="giftcard-card__media">
              <img src="{{ $giftCard['image'] ?: 'https://images.unsplash.com/photo-1513899749857-ae8f0d45f7b8?auto=format&fit=crop&w=1200&q=80' }}" alt="{{ $giftCard['title'] }}" loading="lazy">
            </div>
            <div class="giftcard-card__body">
              <p class="giftcards-kicker">Digital gift card</p>
              <h3>{{ $giftCard['title'] }}</h3>
              <p>{{ $giftCard['summary'] ?: 'A flexible gift card with live denomination variants from the database.' }}</p>
              <div class="giftcard-badges">
                <span class="giftcard-badge">Instant email delivery</span>
                <span class="giftcard-badge">No offering page required</span>
                <span class="giftcard-badge">Variants loaded live</span>
              </div>

              <div class="giftcard-amounts">
                @foreach ($giftCard['denominations'] as $denomination)
                  <div class="giftcard-amount">
                    <div>
                      <strong>{{ $denomination['label'] }}</strong>
                      <span>{{ $denomination['price_label'] }} denomination</span>
                    </div>
                    <div class="giftcard-amount__actions">
                      <button
                        type="button"
                        class="btn-wow btn-wow--outline btn-wow--sm js-add-to-cart js-open-cart"
                        data-product-id="{{ $giftCard['id'] }}"
                        data-variant-id="{{ $denomination['variant_id'] }}"
                        data-variant-label="{{ $denomination['label'] }}"
                        data-title="{{ $giftCard['title'] }}"
                        data-price="{{ number_format($denomination['price'], 2, '.', '') }}"
                        data-image="{{ $giftCard['image'] }}"
                        data-url="/giftcards"
                      >
                        Add to cart
                      </button>
                      <button
                        type="button"
                        class="btn-wow btn-wow--primary btn-wow--sm js-buy-now"
                        data-product-id="{{ $giftCard['id'] }}"
                        data-variant-id="{{ $denomination['variant_id'] }}"
                        data-variant-label="{{ $denomination['label'] }}"
                        data-title="{{ $giftCard['title'] }}"
                        data-price="{{ number_format($denomination['price'], 2, '.', '') }}"
                        data-image="{{ $giftCard['image'] }}"
                        data-url="/giftcards"
                      >
                        Buy now
                      </button>
                    </div>
                  </div>
                @endforeach
              </div>
              <p class="giftcards-note">The add-to-cart buttons use the live variant id and denomination label, so the cart can track the exact gift amount.</p>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</section>
@endsection
