@props(['dialog', 'action', 'method' => 'DELETE', 'icon' => 'trash', 'label', 'title', 'message', 'confirmLabel' => 'Ya, hapus', 'disabled' => false])
<div class="confirm-action">
    <x-admin.icon-button :icon="$icon" :label="$label" :opens="$disabled ? null : $dialog" :disabled="$disabled" />
    @unless ($disabled)
        <x-modal :name="$dialog" :title="$title">
            <p>{{ $message }}</p>
            <x-form :action="$action" :method="$method">
                <x-button-pair>
                    <button type="button" class="btn" x-on:click="open = false">Batal</button>
                    <button type="submit" class="btn btn--danger">{{ $confirmLabel }}</button>
                </x-button-pair>
            </x-form>
        </x-modal>
    @endunless
</div>
