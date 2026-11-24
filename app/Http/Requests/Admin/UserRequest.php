<?php

namespace App\Http\Requests\Admin;

use App\Actions\Fortify\PasswordValidationRules;
use App\Enums\UserRole;
use App\Support\PhoneNumber;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserRequest extends DialogRequest
{
    use PasswordValidationRules;

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'email' => is_string($this->input('email')) ? Str::lower(trim($this->input('email'))) : $this->input('email'),
            'phone' => PhoneNumber::clean($this->input('phone')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'phone' => PhoneNumber::rules(false),
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['nullable', 'string', 'min:8'],
            'password_confirmation' => ['required_with:password', 'nullable', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama wajib diisi.',
            'name.min' => 'Nama minimal 2 karakter.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.string' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar, contoh nama@domain.com.',
            'email.max' => 'Email maksimal 150 karakter.',
            'email.unique' => 'Email sudah dipakai pengguna lain.',
            'role.required' => 'Pilih peran pengguna.',
            'role.enum' => 'Pilih peran pengguna.',
            'password.string' => 'Kata sandi minimal 8 karakter.',
            'password_confirmation.required_with' => 'Ulangi kata sandi belum sama dengan kata sandi di atas.',
            ...$this->passwordMessages(),
            ...PhoneNumber::messages(),
        ];
    }
}
