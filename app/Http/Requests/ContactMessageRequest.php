<?php

namespace App\Http\Requests;

use App\Support\PersonName;
use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => PersonName::rules(),
            'message' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            ...PersonName::messages(),
            'message.required' => 'Pesan wajib diisi.',
            'message.string' => 'Pesan wajib diisi.',
            'message.max' => 'Pesan maksimal 1000 karakter.',
        ];
    }
}
