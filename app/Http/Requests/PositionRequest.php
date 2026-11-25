<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class PositionRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('learn', $this->route('segment')->course);
    }

    public function rules(): array
    {
        return [
            'position' => ['required', 'integer', 'between:0,86400'],
            'duration' => ['required', 'integer', 'between:1,86400'],
        ];
    }
}
