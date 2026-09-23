@extends('layouts.app')

@section('title', $seo['title'] ?? 'Practitioners')
@section('meta_description', $seo['description'] ?? '')

@section('content')
@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Practitioners'],
  ],
  'schemaUrl' => url('/providers'),
])

@include('partials.landing-hero', [
  'heroEyebrow' => 'Meet the people behind the practice',
  'heroTitle' => 'Practitioners',
  'heroIntro' => $seo['description'] ?? 'Discover trusted practitioners and facilitators across therapies and modalities.',
  'heroAsideLabel' => 'A useful starting point',
  'heroAsideTitle' => 'Find the right practitioner',
  'heroAsideText' => 'Learn more about the people behind the sessions before you choose what to book.',
])
@include('partials.hero-meta', [
  'items' => [
    ['label' => 'Trusted practitioners', 'strong' => true],
    ['label' => 'Therapies and modalities'],
    ['label' => 'Profiles and reviews'],
  ],
])

<section class="section">
  <div class="container-page">
    <p class="text-muted">Our verified practitioners and facilitators. Practitioner profiles and booking will return soon.</p>
  </div>
</section>
@endsection
