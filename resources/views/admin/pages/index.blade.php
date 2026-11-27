<x-admin.layout title="Halaman">
    <div class="btn-row admin-toolbar">
        <button type="button" class="btn btn--brand" x-data x-on:click="$dispatch('open-modal', 'page-new')">Halaman baru</button>
    </div>
    @if ($pages->isEmpty())
        <x-empty-state message="Belum ada halaman. Buat halaman pertama, misalnya Tentang Kami." />
    @else
        <div class="panel table-wrap">
            <table class="table">
                <thead>
                    <tr><th class="num">Urutan</th><th>Halaman</th><th>Status</th><th>Footer</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($pages as $page)
                        <tr>
                            <td class="num">{{ $page->sort_order }}</td>
                            <td>
                                <strong>{{ $page->title }}</strong>
                                <div class="muted small"><a href="{{ url('/'.$page->slug) }}">/{{ $page->slug }}</a>{{ $page->system_key ? ', halaman sistem' : '' }}{{ $page->videoId() ? ', landing video' : '' }}</div>
                            </td>
                            <td><span class="{{ $page->is_published ? 'badge badge--ok' : 'badge' }}">{{ $page->is_published ? 'Terbit' : 'Draf' }}</span></td>
                            <td>
                                @if ($page->show_in_footer)
                                    Tampil
                                @else
                                    <span class="muted">Tidak</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-row">
                                    <x-admin.sort-buttons :action="route('admin.pages.move', $page)" :first="$pages->onFirstPage() && $loop->first" :last="! $pages->hasMorePages() && $loop->last" />
                                    <x-admin.icon-button icon="pencil" label="Ubah halaman" :opens="'page-'.$page->id" />
                                    <x-admin.action-button :action="route('admin.pages.toggle', $page)" :icon="$page->is_published ? 'eyeOff' : 'eye'" :label="$page->is_published ? 'Jadikan draf' : 'Terbitkan halaman'" :class="$page->is_published ? '' : 'btn--soft'" />
                                    <x-admin.confirm-action :dialog="'delete-page-'.$page->id" :action="route('admin.pages.destroy', $page)" :label="$page->system_key ? 'Halaman sistem tidak bisa dihapus' : 'Hapus halaman'" :disabled="$page->system_key !== null" title="Hapus halaman?" :message="'Halaman '.$page->title.' akan dihapus permanen.'" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $pages->links() }}
    @endif

    @include('admin.pages.form', ['dialog' => 'page-new', 'page' => new \App\Models\Page(['sort_order' => $nextPosition]), 'action' => route('admin.pages.store'), 'method' => 'POST', 'title' => 'Halaman baru'])
    @foreach ($pages as $page)
        @include('admin.pages.form', ['dialog' => 'page-'.$page->id, 'action' => route('admin.pages.update', $page), 'method' => 'PUT', 'title' => 'Ubah halaman '.$page->title])
    @endforeach
</x-admin.layout>
