<?php

namespace App\Http\Requests\Admin;

class TestimonialRequest extends DialogRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge(collect($this->only(['name', 'note', 'quote']))
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->all());
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:80'],
            'quote' => ['required', 'string', 'min:10', 'max:400'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_visible' => ['required', 'boolean'],
            'is_genuine' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama pembeli wajib diisi.',
            'name.string' => 'Nama pembeli wajib diisi.',
            'name.max' => 'Nama pembeli maksimal 80 karakter.',
            'note.string' => 'Keterangan maksimal 80 karakter.',
            'note.max' => 'Keterangan maksimal 80 karakter.',
            'quote.required' => 'Testimoni wajib diisi, minimal 10 karakter.',
            'quote.string' => 'Testimoni wajib diisi, minimal 10 karakter.',
            'quote.min' => 'Testimoni wajib diisi, minimal 10 karakter.',
            'quote.max' => 'Testimoni maksimal 400 karakter.',
            'sort_order.required' => 'Urutan harus angka bulat mulai dari 1.',
            'sort_order.integer' => 'Urutan harus angka bulat mulai dari 1.',
            'sort_order.min' => 'Urutan harus angka bulat mulai dari 1.',
            'is_visible.required' => 'Pilih status testimoni.',
            'is_visible.boolean' => 'Pilih status testimoni.',
            'is_genuine.required' => 'Pilih apakah testimoni ini asli.',
            'is_genuine.boolean' => 'Pilih apakah testimoni ini asli.',
        ];
    }
}
