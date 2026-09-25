@props([
    'fact' => [],
    'index' => 1,
    'hidden' => false,
])

@php
    $icon = (string) ($fact['icon'] ?? 'info');
    $label = (string) ($fact['label'] ?? '');
    $value = (string) ($fact['value'] ?? '');
    $classes = trim('wow-quick-info__item'.(!empty($fact['highlight']) ? ' wow-quick-info__item--highlight' : ''));
@endphp

<article
    class="{{ $classes }}"
    data-quick-fact="{{ $fact['key'] ?? '' }}"
    data-quick-position="{{ $index }}"
    @if(!empty($fact['dynamic'])) data-quick-dynamic="{{ $fact['dynamic'] }}" @endif
    @if($hidden) hidden @endif
>
    <span class="wow-quick-info__icon" aria-hidden="true">
        @switch($icon)
            @case('globe')
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><path d="M3.8 12h16.4M12 3.5c2.1 2.4 3.2 5.2 3.2 8.5S14.1 18.1 12 20.5" stroke="currentColor" stroke-width="1.55"/></svg>
                @break
            @case('price')
                <svg viewBox="0 0 24 24" fill="none"><path d="M8.5 10.1V8.4a3.5 3.5 0 0 1 7 0M7.2 12h7.9M7.2 16h9.6M9.6 10.1c0 4.8-1.1 7-3.4 9.1h10.9" stroke="currentColor" stroke-width="1.7"/></svg>
                @break
            @case('people')
                <svg viewBox="0 0 24 24" fill="none"><path d="M8.5 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM15.5 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.8 19c.5-3.8 2.3-5.8 5-5.8M20.2 19c-.5-3.8-2.3-5.8-5-5.8M8 19c.5-4 2-6 4-6s3.5 2 4 6" stroke="currentColor" stroke-width="1.6"/></svg>
                @break
            @case('sessions')
                <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="4" width="11" height="14" rx="1.5" stroke="currentColor" stroke-width="1.7"/><path d="M9 8h3M9 11.5h3M9 15h3M16 7h3v13H8v-2" stroke="currentColor" stroke-width="1.7"/></svg>
                @break
            @case('duration')
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.8"/></svg>
                @break
            @case('availability')
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><path d="M8 12.5 10.6 15 16.4 9" stroke="currentColor" stroke-width="1.8"/></svg>
                @break
            @case('audience')
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.7"/><path d="M6.5 19c.6-3.7 2.4-5.6 5.5-5.6s4.9 1.9 5.5 5.6" stroke="currentColor" stroke-width="1.7"/></svg>
                @break
            @case('practitioner')
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.7"/><path d="M6.5 19c.6-3.7 2.4-5.6 5.5-5.6s4.9 1.9 5.5 5.6M12 11.2v4.2M9.7 13.8h4.6" stroke="currentColor" stroke-width="1.7"/></svg>
                @break
            @case('confirmation')
                <svg viewBox="0 0 24 24" fill="none"><rect x="3.5" y="5.5" width="17" height="13" rx="1.5" stroke="currentColor" stroke-width="1.7"/><path d="m4.5 7 7.5 5.6L19.5 7" stroke="currentColor" stroke-width="1.7"/></svg>
                @break
            @default
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s6-5.1 6-11a6 6 0 1 0-12 0c0 5.9 6 11 6 11Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="10" r="2" stroke="currentColor" stroke-width="1.7"/></svg>
        @endswitch
    </span>
    <span class="wow-quick-info__index">{{ str_pad((string) $index, 2, '0', STR_PAD_LEFT) }}</span>
    <p class="wow-quick-info__label" data-quick-label>{{ $label }}</p>
    <p class="wow-quick-info__value{{ !empty($fact['compact']) ? ' wow-quick-info__value--compact' : '' }}" data-quick-value>
        @if($icon === 'availability')<span class="wow-live-dot" aria-hidden="true"></span>@endif{{ $value }}
    </p>
</article>
