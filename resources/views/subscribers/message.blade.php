@extends('layouts.app')

@section('title', ($title ?? 'Subscription update').' | We Offer Wellness®')
@section('meta_description', 'Manage your We Offer Wellness email preferences anytime.')

@section('content')
<section class="section">
  <div class="container-page" style="max-width:720px;">
    <article class="account-card">
      <div class="account-card__header">
        <p class="eyebrow">We Offer Wellness®</p>
        <h1>{{ $title ?? 'Subscription update' }}</h1>
      </div>
      <div class="account-card__body">
        <p class="lead-cart">{{ $body ?? 'Your preferences are saved.' }}</p>
        @if(!empty($cta) && !empty($ctaUrl))
          <div class="mt-4">
            <a class="btn-wow btn-wow--primary" href="{{ $ctaUrl }}">{{ $cta }}</a>
          </div>
        @endif
      </div>
    </article>
  </div>
</section>
@endsection
