<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use App\Support\Slug;
use Illuminate\Validation\Rule;

class PageRequest extends DialogRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'title' => is_string($this->input('title')) ? trim($this->input('title')) : $this->input('title'),
            'slug' => Slug::normalize($this->input('slug')),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:120'],
            'slug' => [...Slug::rules('pages', $this->route('page')?->id), Rule::notIn(Page::RESERVED_SLUGS), 'not_regex:/^livewire/'],
            'content' => ['nullable', 'string', 'max:60000'],
            'is_published' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'show_in_footer' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi, minimal 2 karakter.',
            'title.string' => 'Judul wajib diisi, minimal 2 karakter.',
            'title.min' => 'Judul wajib diisi, minimal 2 karakter.',
            'title.max' => 'Judul maksimal 120 karakter.',
            'slug.not_in' => 'Slug ini dipakai sistem. Pilih slug lain.',
            'slug.not_regex' => 'Slug ini dipakai sistem. Pilih slug lain.',
            'content.string' => 'Isi halaman harus berupa teks.',
            'content.max' => 'Isi halaman maksimal 60.000 karakter.',
            'is_published.required' => 'Pilih status halaman.',
            'is_published.boolean' => 'Pilih status halaman.',
            'sort_order.required' => 'Urutan harus angka bulat mulai dari 1.',
            'sort_order.integer' => 'Urutan harus angka bulat mulai dari 1.',
            'sort_order.min' => 'Urutan harus angka bulat mulai dari 1.',
            'show_in_footer.required' => 'Pilih apakah halaman tampil di footer.',
            'show_in_footer.boolean' => 'Pilih apakah halaman tampil di footer.',
            ...Slug::messages(),
        ];
    }
}
