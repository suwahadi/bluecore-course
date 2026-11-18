@props(['message'])
<div {{ $attributes->merge(['class' => 'empty']) }}>
    <p>{{ $message }}</p>
    {{ $slot }}
</div>
