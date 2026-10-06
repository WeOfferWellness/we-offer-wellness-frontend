@php
    $sectionConfig = is_array($sectionConfig ?? null) ? $sectionConfig : [];
    $heroQuestions = collect(explode(',', (string) data_get($sectionConfig, 'questions', 'What?,Who?,When?,Where?')))
        ->map(fn (string $question): string => trim($question))
        ->filter()
        ->values();
@endphp

<section class="hero" aria-label="About We Offer Wellness®">
    <div class="heroGrid">
        <div>
            <div class="kicker">{{ data_get($sectionConfig, 'eyebrow', 'About us') }}</div>
            <h1 id="about-title">{{ data_get($sectionConfig, 'title', $pageTitle) }}</h1>

            <p>
                {{ data_get($sectionConfig, 'introduction', 'We Offer Wellness® began with a simple idea — born from the frustration of searching for the perfect gift for someone you love. Not another “I saw this in the aisle near the deodorant” gift… a real one. A gift of wellness.') }}
            </p>

            @if($heroQuestions->isNotEmpty())
                <div class="chips" aria-label="The questions we kept asking">
                    @foreach($heroQuestions as $question)
                        <span class="chip">{{ $question }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="heroCard" aria-label="Why we built the platform">
            <div class="cardHd">
                <strong style="font-size:14px; letter-spacing:-.01em;">{{ data_get($sectionConfig, 'card_title', 'Why it matters') }}</strong>
                <span class="muted">{{ data_get($sectionConfig, 'card_meta', 'In one place') }}</span>
            </div>
            <p style="margin-top:8px;">
                {{ data_get($sectionConfig, 'card_body', 'When the internet search becomes endless and overwhelming, wellness shouldn’t feel harder to find than peace itself. We built one home for trusted holistic therapies — easy to browse, clear to understand, and designed around real needs.') }}
            </p>

            <div class="ctaRow">
                <a class="btn primary" href="{{ url(data_get($sectionConfig, 'primary_url', '/help')) }}">
                    {{ data_get($sectionConfig, 'primary_label', 'Visit Help Centre') }} <span aria-hidden="true">→</span>
                </a>
                <a class="btn" href="{{ url(data_get($sectionConfig, 'secondary_url', '/safety-and-contraindications')) }}">
                    {{ data_get($sectionConfig, 'secondary_label', 'Safety info') }} <span aria-hidden="true">→</span>
                </a>
            </div>
        </aside>
    </div>
</section>
