@php
    $published = $course->status === \App\Enums\CourseStatus::Published;
@endphp
<x-admin.layout title="Ubah kursus">
    <div class="panel__head admin-toolbar">
        <div class="btn-row">
            <strong>{{ $course->title }}</strong>
            <span class="{{ $published ? 'badge badge--ok' : 'badge' }}">{{ $published ? 'Terbit' : 'Draf' }}</span>
        </div>
        <div class="btn-row">
            <a class="btn btn--small" href="{{ route('admin.courses.index') }}">Kembali ke daftar</a>
            <x-form :action="route('admin.courses.publish', $course)" class="inline-form">
                <button type="submit" class="btn btn--small btn--soft">{{ $published ? 'Jadikan draf' : 'Terbitkan' }}</button>
            </x-form>
        </div>
    </div>
    <x-form method="PUT" :action="route('admin.courses.update', $course)" :columns="2" class="panel panel--form" enctype="multipart/form-data">
        <h2 class="panel-title field--full">Data kursus</h2>
        @include('admin.courses.fields')
        <x-button-pair class="field--full">
            <a class="btn" href="{{ route('admin.courses.index') }}">Batal</a>
            <button type="submit" class="btn btn--brand">Simpan kursus</button>
        </x-button-pair>
    </x-form>
    @include('admin.courses.segments')
    @include('admin.courses.materials')
</x-admin.layout>
