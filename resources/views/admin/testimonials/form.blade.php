<x-admin.dialog-form :dialog="$dialog" :action="$action" :method="$method" :title="$title" :wide="true" :columns="2" submit-label="Simpan testimoni">
    <x-field name="name" label="Nama pembeli" :value="$testimonial->name" required maxlength="80" />
    <x-field name="note" label="Keterangan (opsional)" :value="$testimonial->note" maxlength="80" placeholder="Kota atau kursus yang dibeli" />
    <x-field name="quote" label="Testimoni" type="textarea" :value="$testimonial->quote" help="Maksimal 400 karakter. Tulis sesuai ucapan pembeli." :full="true" required maxlength="400" rows="4" />
    <x-field name="sort_order" label="Urutan" type="number" :value="$testimonial->sort_order" required min="1" step="1" inputmode="numeric" />
    <x-field name="is_visible" label="Status" type="select" :options="['1' => 'Tampil di beranda', '0' => 'Sembunyikan']" :value="$testimonial->is_visible" />
</x-admin.dialog-form>
