{{-- resources/views/locations/show.blade.php --}}
@extends('layouts.app')

@push('head')
  <title>{{ $seo['title'] ?? (($location['title'] ?? 'Location').' | We Offer Wellness™') }}</title>
  @if(!empty($seo['description']))<meta name="description" content="{{ $seo['description'] }}">@endif
  @if(!empty($seo['robots']))<meta name="robots" content="{{ $seo['robots'] }}">@endif
@endpush

@section('content')
@php
  $locationTitle = (string) ($location['title'] ?? 'Location');
@endphp

<x-marketplace.page-section id="breadcrumbs" component="breadcrumbs" label="Breadcrumbs">
@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Locations', 'url' => route('locations.index')],
    ['label' => $locationTitle],
  ],
  'schemaUrl' => $seo['canonical'] ?? url('/locations/' . ($location['slug'] ?? request()->route('slug'))),
  'currentIcon' => 'location',
])
</x-marketplace.page-section>

<x-marketplace.page-section id="landing-hero" component="landing_hero" label="Landing hero">
@include('partials.landing-hero', [
  'heroEyebrow' => 'Locations',
  'heroTitle' => $locationTitle,
  'heroIntro' => (string) ($seo['description'] ?? ($location['seo_description'] ?? 'Find therapies, classes and events near you.')),
  'heroImage' => $location['image_path'] ?? '',
  'heroLocationLabel' => $locationTitle,
  'heroIsLocation' => true,
  'heroActions' => [
    ['label' => 'All locations', 'href' => route('locations.index'), 'style' => 'outline'],
    ['label' => 'Search all', 'href' => url('/search')],
  ],
  'heroAsideTitle' => null,
  'heroAsideText' => '',
])
</x-marketplace.page-section>

<x-marketplace.page-section id="hero-meta" component="hero_meta" label="Hero details">
@include('partials.hero-meta', [
  'items' => [
    ['label' => 'Local listings', 'strong' => true],
    'Nearby options',
    'Online sessions',
  ],
])
</x-marketplace.page-section>

<x-marketplace.page-section id="offering-filters" component="offering_filters" label="Offering filters & results">
<div class="search-content-wrapper">
  <x-marketplace.offering-filters mode="desktop" :options="[
      'resultsHeading' => $locationTitle.' results',
  ]" />
</div>
</x-marketplace.page-section>
@endsection
