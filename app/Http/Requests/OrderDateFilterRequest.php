<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderDateFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function getRedirectUrl(): string
    {
        return $this->url();
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...(is_string($this->input('from')) && $this->input('from') !== '' ? ['after_or_equal:from'] : [])],
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format' => 'Tanggal harus berformat tahun-bulan-tanggal.',
            'to.date_format' => 'Tanggal harus berformat tahun-bulan-tanggal.',
            'to.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
        ];
    }

    public function from(): ?string
    {
        return $this->validated('from');
    }

    public function to(): ?string
    {
        return $this->validated('to');
    }
}
