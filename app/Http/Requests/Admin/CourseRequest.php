<?php

namespace App\Http\Requests\Admin;

use App\Support\Slug;
use App\Support\UploadRules;

class CourseRequest extends DialogRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            ...collect($this->only(['title', 'level', 'badge', 'fish_type', 'short_description', 'description']))
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->all(),
            'slug' => Slug::normalize($this->input('slug')),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:150'],
            'slug' => Slug::rules('courses', $this->route('course')?->id),
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'level' => ['nullable', 'string', 'max:40'],
            'badge' => ['nullable', 'string', 'max:30'],
            'rating' => ['nullable', 'numeric', 'between:1,5', 'decimal:0,1'],
            'fish_type' => ['nullable', 'string', 'max:40'],
            'price' => ['required', 'integer', 'min:1'],
            'compare_at_price' => ['nullable', 'integer', 'gt:price'],
            'short_description' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:20000'],
            'thumbnail' => UploadRules::image(),
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi, minimal 2 karakter.',
            'title.string' => 'Judul wajib diisi, minimal 2 karakter.',
            'title.min' => 'Judul wajib diisi, minimal 2 karakter.',
            'title.max' => 'Judul maksimal 150 karakter.',
            'category_id.required' => 'Pilih kategori kursus.',
            'category_id.integer' => 'Pilih kategori kursus.',
            'category_id.exists' => 'Pilih kategori kursus.',
            'price.required' => 'Harga wajib lebih dari 0.',
            'price.integer' => 'Harga wajib lebih dari 0.',
            'price.min' => 'Harga wajib lebih dari 0.',
            'compare_at_price.integer' => 'Harga coret harus lebih besar dari harga.',
            'compare_at_price.gt' => 'Harga coret harus lebih besar dari harga.',
            'level.string' => 'Level maksimal 40 karakter.',
            'level.max' => 'Level maksimal 40 karakter.',
            'badge.string' => 'Label sorotan maksimal 30 karakter.',
            'badge.max' => 'Label sorotan maksimal 30 karakter.',
            'rating.numeric' => 'Rating harus angka 1 sampai 5, contoh 4.8.',
            'rating.between' => 'Rating harus angka 1 sampai 5, contoh 4.8.',
            'rating.decimal' => 'Rating harus angka 1 sampai 5, contoh 4.8.',
            'fish_type.string' => 'Jenis ikan maksimal 40 karakter.',
            'fish_type.max' => 'Jenis ikan maksimal 40 karakter.',
            'short_description.string' => 'Deskripsi singkat maksimal 300 karakter.',
            'short_description.max' => 'Deskripsi singkat maksimal 300 karakter.',
            'description.string' => 'Deskripsi lengkap maksimal 20.000 karakter.',
            'description.max' => 'Deskripsi lengkap maksimal 20.000 karakter.',
            ...Slug::messages(),
            ...UploadRules::imageMessages('thumbnail'),
        ];
    }
}
