<?php

namespace App\Http\Requests\Admin;

use App\Support\UploadRules;

class MaterialRequest extends DialogRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (is_string($this->input('title'))) {
            $this->merge(['title' => trim($this->input('title'))]);
        }
    }

    public function rules(): array
    {
        $fileRules = UploadRules::pdf();

        if ($this->route('material') !== null) {
            $fileRules = array_map(fn ($rule) => $rule === 'required' ? 'nullable' : $rule, $fileRules);
        }

        return [
            'title' => ['required', 'string', 'max:150'],
            'file' => $fileRules,
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul materi wajib diisi.',
            'title.string' => 'Judul materi wajib diisi.',
            'title.max' => 'Judul materi maksimal 150 karakter.',
            ...UploadRules::pdfMessages('file'),
        ];
    }
}
