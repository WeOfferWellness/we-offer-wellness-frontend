@props([
    'id',
    'component',
    'label' => null,
])

<div
    class="wow-managed-page-fragment"
    data-page-section-id="{{ $id }}"
    data-page-component="{{ $component }}"
    @if($label) data-page-section-label="{{ $label }}" @endif
>
    {{ $slot }}
</div>
