<?php

namespace App\Http\Requests\Admin;

class EnrollmentRequest extends DialogRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (is_string($this->input('note'))) {
            $this->merge(['note' => trim($this->input('note'))]);
        }
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'note' => ['required', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Pilih kursus.',
            'course_id.integer' => 'Pilih kursus.',
            'course_id.exists' => 'Pilih kursus.',
            'note.required' => 'Alasan wajib diisi.',
            'note.string' => 'Alasan wajib diisi.',
            'note.max' => 'Alasan maksimal 200 karakter.',
        ];
    }
}
