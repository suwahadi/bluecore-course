@props(['action', 'method' => 'POST', 'icon', 'label', 'disabled' => false])
@php
    $verb = strtoupper($method);
@endphp
<form method="POST" action="{{ $action }}" class="inline-form" data-pending>
    @csrf
    @if ($verb !== 'POST')
        @method($verb)
    @endif
    {{ $slot }}
    <x-admin.icon-button type="submit" :icon="$icon" :label="$label" :disabled="$disabled" {{ $attributes }} />
</form>
