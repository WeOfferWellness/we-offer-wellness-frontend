@php
  $label = $label ?? 'Continue';
  $href = $href ?? null;
  $variant = $variant ?? 'primary';
  $size = $size ?? 'md';
  $arrow = (bool) ($arrow ?? false);
  $block = (bool) ($block ?? false);
  $class = trim('btn-wow btn-wow--'.$variant.' btn-wow--'.$size.($arrow ? ' btn-arrow' : '').($block ? ' btn-wow--block' : '').' '.($class ?? ''));
  $ariaLabel = $ariaLabel ?? null;
@endphp

@if($href !== null)
  <a href="{{ $href }}" class="{{ $class }}" @if($ariaLabel) aria-label="{{ $ariaLabel }}" @endif><span class="btn-label">{{ $label }}</span></a>
@else
  <button type="{{ $type ?? 'button' }}" class="{{ $class }}" @if($ariaLabel) aria-label="{{ $ariaLabel }}" @endif><span class="btn-label">{{ $label }}</span></button>
@endif
