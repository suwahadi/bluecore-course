<x-admin.dialog-form :dialog="$dialog" :action="$action" :method="$method" :title="$title" submit-label="Simpan kategori">
    <x-field name="name" label="Nama" :value="$category->name" required maxlength="60" />
    <x-slug-field source="name" :value="$category->slug" :auto="! $category->exists" />
    <x-field name="sort_order" label="Urutan" type="number" :value="$category->sort_order" help="Angka kecil tampil lebih dulu." required min="1" step="1" inputmode="numeric" />
    <x-field name="is_active" label="Status" type="select" :options="['1' => 'Aktif', '0' => 'Nonaktif']" :value="$category->is_active" />
</x-admin.dialog-form>
