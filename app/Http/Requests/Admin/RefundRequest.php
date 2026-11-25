<?php

namespace App\Http\Requests\Admin;

class RefundRequest extends DialogRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (is_string($this->input('refund_note'))) {
            $this->merge(['refund_note' => trim($this->input('refund_note'))]);
        }
    }

    public function rules(): array
    {
        return ['refund_note' => ['required', 'string', 'max:200']];
    }

    public function messages(): array
    {
        return [
            'refund_note.required' => 'Catatan wajib diisi.',
            'refund_note.string' => 'Catatan wajib diisi.',
            'refund_note.max' => 'Catatan maksimal 200 karakter.',
        ];
    }
}
