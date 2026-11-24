<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

abstract class DialogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $dialog = $this->input('_modal');

        if (is_string($dialog) && preg_match('/^[a-z0-9-]{1,40}$/', $dialog)) {
            $this->errorBag = $dialog;
        }
    }
}
