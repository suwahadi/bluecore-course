<?php

namespace App\Http\Requests\Admin;

class MarkPaidRequest extends DialogRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (is_string($this->input('paid_note'))) {
            $this->merge(['paid_note' => trim($this->input('paid_note'))]);
        }
    }

    public function rules(): array
    {
        return ['paid_note' => ['required', 'string', 'max:200']];
    }

    public function messages(): array
    {
        return [
            'paid_note.required' => 'Catatan wajib diisi.',
            'paid_note.string' => 'Catatan wajib diisi.',
            'paid_note.max' => 'Catatan maksimal 200 karakter.',
        ];
    }
}
