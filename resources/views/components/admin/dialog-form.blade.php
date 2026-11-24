@props(['dialog', 'action', 'method' => 'POST', 'title', 'wide' => false, 'submitLabel' => 'Simpan', 'columns' => 1, 'note' => null])
<x-modal :name="$dialog" :title="$title" :wide="$wide" :show="old('_modal') === $dialog">
    <x-form :action="$action" :method="$method" :columns="$columns" {{ $attributes }}>
        <input type="hidden" name="_modal" value="{{ $dialog }}">
        {{ $slot }}
        @if ($note)
            <p class="muted small field--full">{{ $note }}</p>
        @endif
        <x-button-pair>
            <button type="button" class="btn" x-on:click="open = false">Batal</button>
            <button type="submit" class="btn btn--brand">{{ $submitLabel }}</button>
        </x-button-pair>
    </x-form>
    {{ $after ?? '' }}
</x-modal>
