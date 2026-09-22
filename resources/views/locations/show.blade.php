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

@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Locations', 'url' => route('locations.index')],
    ['label' => $locationTitle],
  ],
  'schemaUrl' => $seo['canonical'] ?? url('/locations/' . ($location['slug'] ?? request()->route('slug'))),
  'currentIcon' => 'location',
])

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

<div class="search-content-wrapper">
  @include('search.partials.desktop', [
      'resultsHeading' => $locationTitle.' results',
  ])
</div>
@endsection
