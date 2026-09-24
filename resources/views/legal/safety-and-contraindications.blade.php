{{-- resources/views/legal/safety-and-contraindications.blade.php --}}
@extends('layouts.app')

@php
    $pageTitle = $title ?? 'Safety & Contraindications';
    $pageDescription = $metaDescription ?? 'Safety guidance and contraindications for wellness therapies on We Offer Wellness®.';
    $pageCanonical = $canonical ?? url('/safety-and-contraindications');

    $faqs = [
        [
            'q' => 'How do you vet your wellness providers?',
            'a' => 'Practitioners on We Offer Wellness® are independent professionals. When they join, they confirm that they hold appropriate qualifications, experience and (where required) professional insurance for the services they offer.
We review profiles for clarity and alignment with our wellness guidelines and may remove providers who do not meet our standards or where we have concerns about safety or professionalism.',
        ],
        [
            'q' => 'Are your therapies safe?',
            'a' => 'Yes. Our providers follow industry best practices for health, safety and hygiene. If you have specific concerns, feel free to ask before booking.',
        ],
        [
            'q' => 'I’m pregnant – can I still book?',
            'a' => 'Many practitioners offer pregnancy-friendly options, but not all treatments are suitable during pregnancy or after birth. Please check the session description carefully and let your practitioner know that you’re pregnant. If you’re unsure, we recommend checking with your midwife or GP first.',
        ],
        [
            'q' => 'How do I raise a concern or complaint?',
            'a' => 'You can email us at hello@weofferwellness.co.uk with the details of what happened and the name of the practitioner. We review all safety-related reports and may follow up with you and the practitioner, and where appropriate we may suspend or remove accounts from the platform.',
        ],
    ];
@endphp

@section('title', $pageTitle)

{{-- If your layout supports a "head" section, this will populate meta safely --}}
@section('head')
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $pageCanonical }}">
@endsection

@section('content')
<main class="section" aria-labelledby="page-title">
    <div class="container-page py-10 md:py-14">
        {{-- Header --}}
        <div class="max-w-3xl">
            <div class="kicker">Important information</div>
            <h1 id="page-title" class="mt-2 text-3xl md:text-4xl font-semibold tracking-tight">
                {{ $pageTitle }}
            </h1>

            <div class="mt-5 space-y-4 text-ink-700 leading-relaxed">
                <p>
                    At We Offer Wellness®, your safety and enjoyment of the offering you purchase is paramount.
                </p>
                <p>
                    You should discuss any medical conditions with your chosen practitioner if you have any concerns as to suitability and be guided by their experience and expertise.
                </p>
            </div>
        </div>

        @include('partials.faq-section', [
            'id' => 'safety-faq',
            'eyebrow' => 'Safety',
            'heading' => 'Frequently asked questions',
            'faqs' => collect($faqs ?? [])->map(fn (array $item): array => [
                'q' => $item['q'] ?? '',
                'a' => $item['a'] ?? '',
            ])->all(),
        ])
                                        >
                                            Email hello@weofferwellness.co.uk
                                            <span aria-hidden="true">→</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </details>
                    @endforeach
                </div>

                {{-- Gentle disclaimer (kept minimal, not scary) --}}
                <p class="mt-6 text-sm text-ink-600">
                    This information is general guidance and doesn’t replace medical advice. If you’re unsure, speak to a qualified healthcare professional.
                </p>
            </div>
        </section>
    </div>
</main>
@endsection
