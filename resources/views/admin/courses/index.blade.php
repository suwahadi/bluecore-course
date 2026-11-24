@php
    use App\Enums\CourseStatus;
    use App\Support\Format;
@endphp
<x-admin.layout title="Kursus">
    <div class="btn-row admin-toolbar">
        <button type="button" class="btn btn--brand" x-data x-on:click="$dispatch('open-modal', 'course-new')">Kursus baru</button>
    </div>
    @if ($courses->isEmpty())
        <x-empty-state message="Belum ada kursus. Buat kursus pertama, lalu tambahkan videonya." />
    @else
        <div class="panel table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Sampul</th><th>Kursus</th><th class="num">Harga</th><th class="num">Video</th><th class="num">Peserta</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($courses as $course)
                        @php
                            $published = $course->status === CourseStatus::Published;
                        @endphp
                        <tr>
                            <td>
                                @if ($course->thumbnail_path)
                                    <img class="thumb thumb--rounded admin-thumb" src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="" width="96" height="54" loading="lazy">
                                @else
                                    <x-thumbnail-placeholder :title="$course->title" class="thumb--rounded admin-thumb" />
                                @endif
                            </td>
                            <td><strong>{{ $course->title }}</strong><div class="muted small">{{ $course->category?->name ?? 'Tanpa kategori' }}</div></td>
                            <td class="num">
                                {{ Format::rupiah($course->price) }}
                                @if ($course->compare_at_price)
                                    <div class="strike small">{{ Format::rupiah($course->compare_at_price) }}</div>
                                @endif
                            </td>
                            <td class="num">{{ $course->segments_count }}</td>
                            <td class="num">{{ $course->enrollments_count }}</td>
                            <td><span class="{{ $published ? 'badge badge--ok' : 'badge' }}">{{ $published ? 'Terbit' : 'Draf' }}</span></td>
                            <td>
                                <div class="btn-row">
                                    <x-admin.icon-button icon="pencil" label="Ubah kursus" :href="route('admin.courses.edit', $course)" />
                                    <x-admin.action-button :action="route('admin.courses.publish', $course)" :icon="$published ? 'eyeOff' : 'eye'" :label="$published ? 'Jadikan draf' : 'Terbitkan kursus'" :class="$published ? '' : 'btn--soft'" />
                                    <x-admin.confirm-action :dialog="'delete-course-'.$course->id" :action="route('admin.courses.destroy', $course)" label="Hapus kursus" title="Hapus kursus?" :message="'Kursus '.$course->title.' akan dihapus permanen beserta video, materi, dan progres belajarnya.'" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $courses->links() }}
    @endif

    <x-admin.dialog-form dialog="course-new" :action="route('admin.courses.store')" title="Kursus baru" :wide="true" :columns="2" submit-label="Simpan draf" note="Segmen video dan materi PDF ditambahkan setelah kursus tersimpan sebagai draf." enctype="multipart/form-data">
        @include('admin.courses.fields', ['course' => new \App\Models\Course])
    </x-admin.dialog-form>
</x-admin.layout>
