<x-field name="title" label="Judul" :value="$course->title" required maxlength="150" placeholder="Judul kursus" />
<x-slug-field :value="$course->slug" :auto="! $course->exists" />
<x-field name="category_id" label="Kategori" type="select" :options="['' => 'Pilih kategori'] + $categoryOptions" :value="$course->category_id" :full="true" required />
<x-field name="price" label="Harga (rupiah)" type="number" :value="$course->price" required min="1" step="1" inputmode="numeric" />
<x-field name="compare_at_price" label="Harga coret (opsional)" type="number" :value="$course->compare_at_price" min="1" step="1" inputmode="numeric" />
<x-field name="short_description" label="Deskripsi singkat" type="textarea" :value="$course->short_description" :full="true" rows="3" maxlength="300" />
<x-field name="description" label="Deskripsi lengkap" type="textarea" :value="$course->description" :full="true" rows="8" maxlength="20000" />
<div class="thumb-field field--full">
    @if ($course->thumbnail_path)
        <img class="thumb thumb--rounded" src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="" width="168" height="95">
    @else
        <x-thumbnail-placeholder :title="$course->title ?: 'Sampul kursus'" class="thumb--rounded" />
    @endif
    <x-field name="thumbnail" label="Gambar sampul" type="file" accept="image/jpeg,image/png,image/webp" />
</div>
