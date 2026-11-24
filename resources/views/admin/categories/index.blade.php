<x-admin.layout title="Kategori">
    <div class="btn-row admin-toolbar">
        <button type="button" class="btn btn--brand" x-data x-on:click="$dispatch('open-modal', 'category-new')">Kategori baru</button>
    </div>
    @if ($categories->isEmpty())
        <x-empty-state message="Belum ada kategori. Buat kategori pertama." />
    @else
        <div class="panel table-wrap">
            <table class="table">
                <thead>
                    <tr><th class="num">Urutan</th><th>Kategori</th><th class="num">Kursus</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td class="num">{{ $category->sort_order }}</td>
                            <td><strong>{{ $category->name }}</strong><div class="muted small">{{ $category->slug }}</div></td>
                            <td class="num">{{ $category->courses_count }}</td>
                            <td><span class="{{ $category->is_active ? 'badge badge--ok' : 'badge' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>
                                <div class="btn-row">
                                    <x-admin.sort-buttons :action="route('admin.categories.move', $category)" :first="$categories->onFirstPage() && $loop->first" :last="! $categories->hasMorePages() && $loop->last" />
                                    <x-admin.icon-button icon="pencil" label="Ubah kategori" :opens="'category-'.$category->id" />
                                    <x-admin.action-button :action="route('admin.categories.toggle', $category)" icon="power" :label="$category->is_active ? 'Nonaktifkan kategori' : 'Aktifkan kategori'" />
                                    <x-admin.confirm-action :dialog="'delete-category-'.$category->id" :action="route('admin.categories.destroy', $category)" label="Hapus kategori" title="Hapus kategori?" :message="'Kategori '.$category->name.' akan dihapus permanen.'" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $categories->links() }}
    @endif
    <p class="muted small admin-note">Kategori nonaktif tidak tampil di pintasan topik dan filter katalog, tapi kursusnya tetap bisa dicari. Kategori yang masih dipakai kursus tidak bisa dihapus.</p>

    @include('admin.categories.form', ['dialog' => 'category-new', 'category' => new \App\Models\Category(['sort_order' => $nextPosition]), 'action' => route('admin.categories.store'), 'method' => 'POST', 'title' => 'Kategori baru'])
    @foreach ($categories as $category)
        @include('admin.categories.form', ['dialog' => 'category-'.$category->id, 'action' => route('admin.categories.update', $category), 'method' => 'PUT', 'title' => 'Ubah kategori '.$category->name])
    @endforeach
</x-admin.layout>
