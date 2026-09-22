@php
    $faqId = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($id ?? 'wow-faq')) ?: 'wow-faq';
    $faqItems = collect($faqs ?? [])->map(function ($faq): ?array {
        if (is_array($faq)) {
            $question = trim((string) ($faq['question'] ?? $faq['q'] ?? ''));
            $answer = $faq['answer'] ?? $faq['a'] ?? '';
        } else {
            return null;
        }

        if ($question === '' || (is_string($answer) && trim($answer) === '')) {
            return null;
        }

        return ['question' => $question, 'answer' => $answer];
    })->filter()->values();
@endphp

@if($faqItems->isNotEmpty())
<section class="wow-faq" id="{{ $faqId }}" aria-labelledby="{{ $faqId }}-title" data-wow-faq>
    <div class="wow-component-container">
        <header class="wow-faq__header">
            <p class="wow-faq__eyebrow">{{ $eyebrow ?? 'Helpful to know' }}</p>
            <h2 id="{{ $faqId }}-title">{{ $heading ?? 'Frequently asked questions' }}</h2>
            @if(!empty($intro))
                <p class="wow-faq__intro">{{ $intro }}</p>
            @endif
        </header>

        <div class="wow-faq__list">
            @foreach($faqItems as $index => $faq)
                @php
                    $itemId = $faqId.'-'.($index + 1);
                    $open = ($firstOpen ?? true) && $index === 0;
                    $answer = $faq['answer'];
                @endphp
                <div class="wow-faq__item{{ $open ? ' is-open' : '' }}">
                    <button class="wow-faq__question" type="button" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="{{ $itemId }}-answer" id="{{ $itemId }}-question">
                        <span>{{ $faq['question'] }}</span>
                        <span class="wow-faq__icon" aria-hidden="true"><span></span><span></span></span>
                    </button>
                    <div class="wow-faq__answer" id="{{ $itemId }}-answer" role="region" aria-labelledby="{{ $itemId }}-question">
                        <div class="wow-faq__answer-inner">
                            <div class="wow-faq__answer-content">
                                @if(is_array($answer))
                                    @foreach($answer as $paragraph)
                                        <p>{{ $paragraph }}</p>
                                    @endforeach
                                @else
                                    <p>{{ $answer }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@once
<style>
    .wow-component-container{width:min(calc(100% - 48px),1280px);margin-inline:auto}.wow-faq{padding:62px 0 70px;background:#fafafa !important;border:0}
    .wow-faq__header{margin-bottom:28px}.wow-faq__eyebrow{margin:0 0 9px;color:var(--wow-green,#4f9482);font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase}.wow-faq h2{margin:0;color:var(--wow-green-dark,#0b3028);font-family:"Playfair Display",Georgia,serif;font-size:clamp(40px,4vw,54px);font-weight:500;line-height:1;letter-spacing:-.04em}.wow-faq__intro{max-width:720px;margin:12px 0 0;color:var(--wow-muted,#68736f);font-size:15px;line-height:1.55}
    .wow-faq__list{border-top:1px solid var(--wow-line,#dce4e0)}.wow-faq__item{border-bottom:1px solid var(--wow-line,#dce4e0)}.wow-faq__question{width:100%;display:grid;grid-template-columns:minmax(0,1fr) 38px;align-items:center;gap:24px;min-height:76px;padding:0;border:0;background:transparent;color:var(--wow-text,#17201d);font:600 16px/1.35 "Instrument Sans",sans-serif;text-align:left;cursor:pointer}.wow-faq__question:hover{color:var(--wow-green-dark,#0b3028)}.wow-faq__question:focus-visible{outline:2px solid var(--wow-green,#4f9482);outline-offset:4px}.wow-faq__icon{position:relative;display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;justify-self:end;color:var(--wow-text,#17201d)}.wow-faq__icon span{position:absolute;display:block;background:currentColor;border-radius:99px;transition:transform .18s ease,opacity .18s ease}.wow-faq__icon span:first-child{width:14px;height:1.5px}.wow-faq__icon span:last-child{width:1.5px;height:14px}.wow-faq__question[aria-expanded=true] .wow-faq__icon{color:var(--wow-green,#4f9482)}.wow-faq__question[aria-expanded=true] .wow-faq__icon span:last-child{transform:rotate(90deg);opacity:0}
    .wow-faq__answer{display:grid;grid-template-rows:0fr;transition:grid-template-rows .24s ease}.wow-faq__item.is-open .wow-faq__answer{grid-template-rows:1fr}.wow-faq__answer-inner{min-height:0;overflow:hidden}.wow-faq__answer-content{max-width:1040px;padding:0 70px 30px 0}.wow-faq__answer-content p{margin:0 0 14px;color:#404b47;font-size:15px;line-height:1.7}.wow-faq__answer-content p:last-child{margin-bottom:0}
    @media(max-width:575.98px){.wow-component-container{width:calc(100% - 30px)}.wow-faq{padding:42px 0 46px}.wow-faq h2{font-size:38px}.wow-faq__intro{font-size:14px}.wow-faq__question{min-height:68px;grid-template-columns:minmax(0,1fr) 30px;gap:16px;font-size:14px}.wow-faq__icon{width:28px;height:28px}.wow-faq__answer-content{padding:0 34px 24px 0}.wow-faq__answer-content p{font-size:14px;line-height:1.65}}@media(prefers-reduced-motion:reduce){.wow-faq__answer,.wow-faq__icon span{transition:none}}
</style>
@endonce

@once
<script>
document.querySelectorAll('[data-wow-faq]').forEach(function (faq) {
    faq.querySelectorAll('.wow-faq__question').forEach(function (button) {
        button.addEventListener('click', function () {
            const item = button.closest('.wow-faq__item');
            const expanded = button.getAttribute('aria-expanded') === 'true';
            item.classList.toggle('is-open', !expanded);
            button.setAttribute('aria-expanded', String(!expanded));
        });
    });
});
</script>
@endonce
@endif
