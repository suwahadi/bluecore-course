@props(['name', 'title', 'wide' => false, 'show' => false])
<div x-data="{ open: {{ $show ? 'true' : 'false' }} }" x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true" x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false" x-on:keydown.escape.window="open = false">
    <div class="modal-backdrop" x-show="open" x-cloak x-on:click.self="open = false">
        <div class="{{ $wide ? 'modal modal--wide' : 'modal' }}" role="dialog" aria-modal="true" aria-labelledby="modal-{{ $name }}-title" x-trap.noscroll="open">
            <div class="modal__head">
                <h3 id="modal-{{ $name }}-title">{{ $title }}</h3>
                <button type="button" class="modal__close" aria-label="Tutup" x-on:click="open = false"><x-icon name="x" /></button>
            </div>
            <div class="modal__body">{{ $slot }}</div>
        </div>
    </div>
</div>
