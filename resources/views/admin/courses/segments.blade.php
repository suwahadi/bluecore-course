<x-form method="PUT" :action="route('admin.courses.segments.update', $course)" class="panel panel--form" x-data="segmentEditor({{ \Illuminate\Support\Js::from($segmentRows) }})">
    <h2 class="panel-title">Video kursus</h2>
    <p class="field__help">Satu baris per video. Tempel tautan video YouTube unlisted untuk setiap segmen.</p>
    @error('segments')
        <p class="field__error" role="alert">{{ $message }}</p>
    @enderror
    <ol class="seg-form">
        <template x-for="(row, index) in rows" x-bind:key="row.key">
            <li class="seg-form__row seg-editor__row">
                <input type="hidden" x-bind:name="`segments[${index}][id]`" x-bind:value="row.id ?? ''">
                <div class="field">
                    <label x-bind:for="`segment-${row.key}-title`" x-text="`Judul segmen ${index + 1}`"></label>
                    <input type="text" maxlength="150" required x-bind:id="`segment-${row.key}-title`" x-bind:name="`segments[${index}][title]`" x-model="row.title" x-bind:aria-invalid="row.errors.title ? 'true' : null" x-bind:aria-describedby="row.errors.title ? `segment-${row.key}-title-error` : null">
                    <span class="field__error" role="alert" x-show="row.errors.title" x-text="row.errors.title" x-bind:id="`segment-${row.key}-title-error`"></span>
                </div>
                <div class="field">
                    <label x-bind:for="`segment-${row.key}-url`">URL YouTube</label>
                    <input type="url" required placeholder="https://youtu.be/..." autocapitalize="off" spellcheck="false" x-bind:id="`segment-${row.key}-url`" x-bind:name="`segments[${index}][url]`" x-model="row.url" x-bind:aria-invalid="row.errors.url ? 'true' : null" x-bind:aria-describedby="row.errors.url ? `segment-${row.key}-url-error` : null">
                    <span class="field__error" role="alert" x-show="row.errors.url" x-text="row.errors.url" x-bind:id="`segment-${row.key}-url-error`"></span>
                </div>
                <fieldset class="seg-editor__duration">
                    <legend>Durasi (opsional)</legend>
                    <label class="seg-editor__time">
                        <input type="number" min="0" max="600" step="1" inputmode="numeric" x-bind:name="`segments[${index}][minutes]`" x-model="row.minutes" x-bind:aria-invalid="row.errors.duration ? 'true' : null">
                        <span>menit</span>
                    </label>
                    <label class="seg-editor__time">
                        <input type="number" min="0" max="59" step="1" inputmode="numeric" x-bind:name="`segments[${index}][seconds]`" x-model="row.seconds" x-bind:aria-invalid="row.errors.duration ? 'true' : null">
                        <span>detik</span>
                    </label>
                    <span class="field__error" role="alert" x-show="row.errors.duration" x-text="row.errors.duration"></span>
                </fieldset>
                <div class="btn-row seg-editor__actions">
                    <button type="button" class="btn btn-icon" aria-label="Naikkan urutan" title="Naikkan urutan" x-bind:disabled="index === 0" x-on:click="move(index, -1)"><x-icon name="arrowUp" /></button>
                    <button type="button" class="btn btn-icon" aria-label="Turunkan urutan" title="Turunkan urutan" x-bind:disabled="index === rows.length - 1" x-on:click="move(index, 1)"><x-icon name="arrowDown" /></button>
                    <button type="button" class="btn btn-icon btn--danger" aria-label="Hapus video" title="Hapus video" x-on:click="askRemove(index)"><x-icon name="trash" /></button>
                </div>
            </li>
        </template>
    </ol>
    <p class="muted" x-show="rows.length === 0">Belum ada video. Tambahkan baris pertama.</p>
    <div class="btn-row">
        <button type="button" class="btn" x-on:click="add()">Tambah baris</button>
    </div>
    <x-button-pair>
        <a class="btn" href="{{ route('admin.courses.edit', $course) }}">Batal</a>
        <button type="submit" class="btn btn--brand">Simpan video</button>
    </x-button-pair>
    <x-modal name="segment-remove" title="Hapus video ini?">
        <p>Baris video akan dihapus dari daftar. Perubahan tersimpan setelah Anda menekan Simpan video.</p>
        <x-button-pair>
            <button type="button" class="btn" x-on:click="open = false">Batal</button>
            <button type="button" class="btn btn--danger" x-on:click="confirmRemove(); open = false">Ya, hapus</button>
        </x-button-pair>
    </x-modal>
</x-form>
