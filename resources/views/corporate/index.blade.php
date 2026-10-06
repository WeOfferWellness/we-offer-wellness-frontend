@extends('layouts.app')

@section('title', $seo['title'] ?? 'Corporate Wellness')
@section('meta_description', $seo['description'] ?? '')

@section('content')
<x-marketplace.page-section id="breadcrumbs" component="breadcrumbs" label="Breadcrumbs">
@include('partials.breadcrumbs', [
  'crumbs' => [
    ['label' => 'Home', 'url' => url('/')],
    ['label' => 'Corporate Wellness'],
  ],
  'schemaUrl' => url('/corporate'),
])
</x-marketplace.page-section>

<x-marketplace.page-section id="corporate-content" component="corporate_content" label="Corporate content">
<section class="section">
  <div class="container-page">
    <h1 class="display-5 mb-3">Corporate Wellness</h1>
    <p class="lead text-muted">Workplace wellbeing programmes: workshops, meditation, breathwork and more.</p>
    <p>We tailor sessions for stress, sleep and energy across in-person and online formats.</p>
  </div>
</section>
</x-marketplace.page-section>
@endsection
