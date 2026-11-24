<x-admin.dialog-form :dialog="$dialog" :action="$action" :method="$method" :title="$title" :wide="true" :columns="2" submit-label="Simpan halaman">
    <x-field name="title" label="Judul" :value="$page->title" required maxlength="120" placeholder="Misalnya Tentang Kami" />
    <x-slug-field :value="$page->slug" :auto="! $page->exists" />
    <x-field name="content" label="Isi" type="textarea" :value="$page->content" help='Markdown: baris "## Judul" untuk judul, "- " untuk daftar.' :full="true" rows="10" maxlength="60000" />
    <x-field name="is_published" label="Status" type="select" :options="['1' => 'Terbit', '0' => 'Draf']" :value="$page->is_published" />
    <x-field name="sort_order" label="Urutan di footer" type="number" :value="$page->sort_order" required min="1" step="1" inputmode="numeric" />
    <x-checkbox name="show_in_footer" label="Tampilkan di footer" :checked="$page->show_in_footer" />
</x-admin.dialog-form>
