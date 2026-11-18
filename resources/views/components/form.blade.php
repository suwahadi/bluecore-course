@props(['action', 'method' => 'POST', 'columns' => 1])
@php
    $verb = strtoupper($method);
    $htmlMethod = in_array($verb, ['GET', 'POST'], true) ? $verb : 'POST';
@endphp
<form method="{{ $htmlMethod }}" action="{{ $action }}" novalidate data-pending {{ $attributes->merge(['class' => (int) $columns === 2 ? 'form-grid form-grid--2' : 'form-grid']) }}>
    @if ($verb !== 'GET')
        @csrf
    @endif
    @if ($htmlMethod !== $verb)
        @method($verb)
    @endif
    {{ $slot }}
</form>
