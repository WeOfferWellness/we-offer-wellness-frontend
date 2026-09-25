@props(['facts' => []])

@php
    $facts = array_values(array_filter((array) $facts, fn ($fact) => is_array($fact) && !empty($fact['value'])));
    $initialFacts = array_slice(array_values(array_filter($facts, fn ($fact) => empty($fact['defer']))), 0, 5);
    $initialKeys = array_column($initialFacts, 'key');
    $deferredFacts = array_values(array_filter($facts, fn ($fact) => !in_array($fact['key'] ?? null, $initialKeys, true)));
@endphp

@if($facts)
    <section class="wow-quick-info" aria-label="Offering quick information" data-quick-info>
        <div class="wow-quick-info__grid">
            @foreach($initialFacts as $index => $fact)
                @include('offering.partials.quick_info.item', ['fact' => $fact, 'index' => $index + 1])
            @endforeach
            @foreach($deferredFacts as $index => $fact)
                @include('offering.partials.quick_info.item', ['fact' => $fact, 'index' => count($initialFacts) + $index + 1, 'hidden' => true])
            @endforeach
        </div>
    </section>
@endif
