@php
    $offeringButtonLabel = $label ?? 'VIEW & BOOK';
    $offeringButtonHref = $href ?? '#';
    $offeringButtonAria = $ariaLabel ?? $offeringButtonLabel;
    $offeringButtonClass = trim('btn-wow btn-wow--primary btn-wow--card wow410-card__button '.($class ?? ''));
@endphp

<a href="{{ $offeringButtonHref }}"
   class="{{ $offeringButtonClass }}"
   aria-label="{{ $offeringButtonAria }}"
   @if(!empty($target)) target="{{ $target }}" @endif
   @if(!empty($rel)) rel="{{ $rel }}" @endif>
    <span class="btn-label">{{ $offeringButtonLabel }}</span>
</a>
