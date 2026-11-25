@props(['progress', 'current' => null, 'thin' => false])
<div {{ $attributes->merge(['class' => $thin ? 'seg-bar seg-bar--thin' : 'seg-bar']) }} aria-hidden="true">
    @foreach ($progress->cells as $done)
        <span data-done="{{ $done ? 'true' : 'false' }}" @if ($current === $loop->index) data-current="true" @endif></span>
    @endforeach
</div>
