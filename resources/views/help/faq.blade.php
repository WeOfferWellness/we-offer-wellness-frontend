@extends('layouts.app')

@section('title', $seo['title'] ?? 'FAQ | We Offer Wellness®')
@section('meta_description', $seo['description'] ?? '')
@section('meta_robots', 'index,follow')

@section('content')
<section class="section">
  <div class="container-page">
    @include('partials.breadcrumbs', [
      'crumbs' => [
        ['label' => 'Home', 'url' => url('/')],
        ['label' => 'Help Centre', 'url' => url('/help')],
        ['label' => 'FAQ'],
      ],
      'schemaUrl' => url('/help/faq'),
    ])

    <div class="mb-4">
      <p class="text-uppercase fw-bold text-muted mb-2" style="letter-spacing:.18em;font-size:.76rem;">Help Centre</p>
      <h1 class="display-5 mb-3">Frequently asked questions</h1>
      <p class="lead text-muted mb-0">Answers to the most common booking, payment and account questions.</p>
    </div>

    @include('partials.faq-section', [
      'id' => 'help-faq',
      'eyebrow' => 'Help Centre',
      'heading' => 'Frequently asked questions',
      'intro' => 'Answers to the most common booking, payment and account questions.',
      'faqs' => [
        ['q' => 'How do I manage a booking?', 'a' => 'Visit your confirmation email to reschedule or cancel, or message the practitioner directly from your account.'],
        ['q' => 'What if I need to cancel?', 'a' => 'Each listing includes a cancellation window. If you cannot find it, contact support at /contact?topic=support.'],
        ['q' => 'How do I reset my password?', 'a' => 'Use the forgot-password link on sign in. If the email does not arrive, check spam/junk and search for We Offer Wellness®.'],
        ['q' => 'Do I need any equipment?', 'a' => 'Most therapies only require comfortable clothing and a quiet space. Classes will note props if needed.'],
        ['q' => 'How do I check whether a session is suitable?', 'a' => 'Read the listing details and our Safety & Contraindications guidance before booking if you are unsure.'],
      ],
    ])
  </div>
</section>
@endsection
