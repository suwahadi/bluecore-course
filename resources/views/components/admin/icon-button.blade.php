@props(['icon', 'label', 'opens' => null, 'href' => null])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-icon']) }} aria-label="{{ $label }}" title="{{ $label }}"><x-icon :name="$icon" /></a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-icon']) }} aria-label="{{ $label }}" title="{{ $label }}" @if ($opens) x-data x-on:click="$dispatch('open-modal', '{{ $opens }}')" @endif><x-icon :name="$icon" /></button>
@endif
