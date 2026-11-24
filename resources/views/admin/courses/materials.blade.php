@php
    use App\Support\Format;
@endphp
<div class="panel">
    <div class="panel__head">
        <h2 class="panel-title">Materi PDF</h2>
        <button type="button" class="btn btn--small" x-data x-on:click="$dispatch('open-modal', 'material-new')">Tambah materi</button>
    </div>
    @if ($course->materials->isEmpty())
        <x-empty-state message="Belum ada materi PDF untuk kursus ini." />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Materi</th><th class="num">Ukuran</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($course->materials as $material)
                        <tr>
                            <td><strong>{{ $material->title }}</strong><div class="muted small">{{ $material->original_name }}</div></td>
                            <td class="num">{{ Format::fileSize($material->file_size) }}</td>
                            <td>
                                <div class="btn-row">
                                    <x-admin.sort-buttons :action="route('admin.courses.materials.move', [$course, $material])" :first="$loop->first" :last="$loop->last" />
                                    <x-admin.icon-button icon="refresh" label="Ganti file" :opens="'material-'.$material->id" />
                                    <x-admin.confirm-action :dialog="'delete-material-'.$material->id" :action="route('admin.courses.materials.destroy', [$course, $material])" label="Hapus materi" title="Hapus materi?" :message="'Materi '.$material->title.' akan dihapus permanen.'" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<x-admin.dialog-form dialog="material-new" :action="route('admin.courses.materials.store', $course)" title="Tambah materi" submit-label="Simpan materi" enctype="multipart/form-data">
    <x-field name="title" label="Judul materi" required maxlength="150" />
    <x-field name="file" label="File PDF" type="file" accept="application/pdf" required />
</x-admin.dialog-form>
@foreach ($course->materials as $material)
    <x-admin.dialog-form :dialog="'material-'.$material->id" :action="route('admin.courses.materials.update', [$course, $material])" method="PUT" :title="'Ubah materi '.$material->title" submit-label="Simpan materi" enctype="multipart/form-data">
        <x-field name="title" label="Judul materi" :value="$material->title" required maxlength="150" />
        <x-field name="file" label="Ganti file PDF (opsional)" type="file" accept="application/pdf" />
    </x-admin.dialog-form>
@endforeach
