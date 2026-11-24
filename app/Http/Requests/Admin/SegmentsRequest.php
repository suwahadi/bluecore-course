<?php

namespace App\Http\Requests\Admin;

use App\Rules\YouTubeLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SegmentsRequest extends FormRequest
{
    public const MAX_SEGMENTS = 200;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'segments' => ['nullable', 'array', 'max:'.self::MAX_SEGMENTS],
            'segments.*' => ['array'],
            'segments.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('course_segments', 'id')->where('course_id', $this->route('course')->id)],
            'segments.*.title' => ['required', 'string', 'max:150'],
            'segments.*.url' => ['bail', 'required', 'string', 'max:255', new YouTubeLink],
            'segments.*.minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'segments.*.seconds' => ['nullable', 'integer', 'min:0', 'max:59'],
        ];
    }

    public function messages(): array
    {
        return [
            'segments.array' => 'Daftar video tidak terbaca. Muat ulang halaman lalu coba lagi.',
            'segments.max' => 'Maksimal '.self::MAX_SEGMENTS.' video per kursus.',
            'segments.*.id.*' => 'Video ini bukan milik kursus ini. Muat ulang halaman lalu coba lagi.',
            'segments.*.title.required' => 'Judul video wajib diisi.',
            'segments.*.title.string' => 'Judul video wajib diisi.',
            'segments.*.title.max' => 'Judul video maksimal 150 karakter.',
            'segments.*.url.required' => 'Tautan YouTube belum benar.',
            'segments.*.url.string' => 'Tautan YouTube belum benar.',
            'segments.*.url.max' => 'Tautan YouTube belum benar.',
            'segments.*.minutes.*' => 'Menit harus angka bulat.',
            'segments.*.seconds.*' => 'Detik harus angka 0 sampai 59.',
        ];
    }

    public function rows(): array
    {
        return array_values($this->validated('segments') ?? []);
    }
}
