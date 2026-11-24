<x-admin.layout title="Testimoni">
    <div class="btn-row admin-toolbar">
        <button type="button" class="btn btn--brand" x-data x-on:click="$dispatch('open-modal', 'testimonial-new')">Testimoni baru</button>
    </div>
    @if ($testimonials->isEmpty())
        <x-empty-state message="Belum ada testimoni. Bagian testimoni di beranda baru tampil setelah ada testimoni yang ditampilkan." />
    @else
        <div class="panel table-wrap">
            <table class="table">
                <thead>
                    <tr><th class="num">Urutan</th><th>Pembeli</th><th>Testimoni</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($testimonials as $testimonial)
                        <tr>
                            <td class="num">{{ $testimonial->sort_order }}</td>
                            <td>
                                <strong>{{ $testimonial->name }}</strong>
                                @if ($testimonial->note)
                                    <div class="muted small">{{ $testimonial->note }}</div>
                                @endif
                            </td>
                            <td class="testimonial-cell">{{ Str::limit($testimonial->quote, 80) }}</td>
                            <td><span class="{{ $testimonial->is_visible ? 'badge badge--ok' : 'badge' }}">{{ $testimonial->is_visible ? 'Tampil' : 'Disembunyikan' }}</span></td>
                            <td>
                                <div class="btn-row">
                                    <x-admin.sort-buttons :action="route('admin.testimonials.move', $testimonial)" :first="$testimonials->onFirstPage() && $loop->first" :last="! $testimonials->hasMorePages() && $loop->last" />
                                    <x-admin.icon-button icon="pencil" label="Ubah testimoni" :opens="'testimonial-'.$testimonial->id" />
                                    <x-admin.action-button :action="route('admin.testimonials.toggle', $testimonial)" :icon="$testimonial->is_visible ? 'eyeOff' : 'eye'" :label="$testimonial->is_visible ? 'Sembunyikan testimoni' : 'Tampilkan testimoni'" :class="$testimonial->is_visible ? '' : 'btn--soft'" />
                                    <x-admin.confirm-action :dialog="'delete-testimonial-'.$testimonial->id" :action="route('admin.testimonials.destroy', $testimonial)" label="Hapus testimoni" title="Hapus testimoni?" :message="'Testimoni dari '.$testimonial->name.' akan dihapus permanen.'" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $testimonials->links() }}
    @endif

    @include('admin.testimonials.form', ['dialog' => 'testimonial-new', 'testimonial' => new \App\Models\Testimonial(['sort_order' => $nextPosition]), 'action' => route('admin.testimonials.store'), 'method' => 'POST', 'title' => 'Testimoni baru'])
    @foreach ($testimonials as $testimonial)
        @include('admin.testimonials.form', ['dialog' => 'testimonial-'.$testimonial->id, 'action' => route('admin.testimonials.update', $testimonial), 'method' => 'PUT', 'title' => 'Ubah testimoni'])
    @endforeach
</x-admin.layout>
