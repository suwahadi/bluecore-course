<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Support\Slug;
use Closure;

class CategoryRequest extends DialogRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'slug' => Slug::normalize($this->input('slug')),
        ]);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'min:2', 'max:60', $this->uniqueNameIgnoringCase($category)],
            'slug' => Slug::rules('categories', $category?->id),
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi, minimal 2 karakter.',
            'name.string' => 'Nama kategori wajib diisi, minimal 2 karakter.',
            'name.min' => 'Nama kategori wajib diisi, minimal 2 karakter.',
            'name.max' => 'Nama kategori maksimal 60 karakter.',
            'sort_order.required' => 'Urutan harus angka bulat mulai dari 1.',
            'sort_order.integer' => 'Urutan harus angka bulat mulai dari 1.',
            'sort_order.min' => 'Urutan harus angka bulat mulai dari 1.',
            'is_active.required' => 'Pilih status kategori.',
            'is_active.boolean' => 'Pilih status kategori.',
            ...Slug::messages(),
        ];
    }

    private function uniqueNameIgnoringCase(?Category $category): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($category) {
            $taken = Category::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $value)])
                ->when($category, fn ($query) => $query->whereKeyNot($category->id))
                ->exists();

            if ($taken) {
                $fail('Nama kategori sudah dipakai.');
            }
        };
    }
}
