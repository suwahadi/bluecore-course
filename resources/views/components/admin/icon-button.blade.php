@props(['icon', 'label', 'opens' => null])
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-icon']) }} aria-label="{{ $label }}" title="{{ $label }}" @if ($opens) x-data x-on:click="$dispatch('open-modal', '{{ $opens }}')" @endif><x-icon :name="$icon" /></button>
