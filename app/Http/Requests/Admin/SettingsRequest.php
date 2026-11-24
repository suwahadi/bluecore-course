<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourseStatus;
use App\Rules\YouTubeLink;
use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingsRequest extends FormRequest
{
    private const LINK_NAMES = [
        'instagram_url' => 'Instagram',
        'youtube_url' => 'YouTube',
        'tiktok_url' => 'TikTok',
        'shopee_url' => 'Shopee',
        'tokopedia_url' => 'Tokopedia',
        'tiktokshop_url' => 'TikTok Shop',
    ];

    private const LEAD_MAGNET_URL_MESSAGE = 'Tautan video gratis harus tautan YouTube yang diawali https://.';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = collect(config('settings'))->except('logo')->map(fn (array $definition) => $definition['rules'])->all();

        $rules['featured_course_id'] = ['nullable', 'integer', Rule::exists('courses', 'id')->where('status', CourseStatus::Published->value)];
        $rules['lead_magnet_youtube_url'] = [...$rules['lead_magnet_youtube_url'], new YouTubeLink(self::LEAD_MAGNET_URL_MESSAGE)];
        $rules['logo_upload'] = UploadRules::image();
        $rules['remove_logo'] = ['nullable', 'boolean'];

        return $rules;
    }

    public function messages(): array
    {
        $links = collect(self::LINK_NAMES)->flatMap(fn (string $name, string $key) => collect(['url', 'starts_with', 'max'])
            ->mapWithKeys(fn (string $rule) => ["{$key}.{$rule}" => "Tautan {$name} harus diawali https://."]))
            ->all();

        return [
            'site_name.required' => 'Nama situs wajib diisi.',
            'site_name.max' => 'Nama situs maksimal 60 karakter.',
            'site_slogan.max' => 'Slogan maksimal 120 karakter.',
            'site_description.max' => 'Deskripsi singkat maksimal 300 karakter.',
            'address.max' => 'Alamat maksimal 200 karakter.',
            'phone.max' => 'Telepon maksimal 30 karakter.',
            'whatsapp.regex' => 'Nomor WhatsApp harus diawali 62 tanpa tanda plus.',
            'email.email' => 'Format email belum benar, contoh nama@domain.com.',
            'email.max' => 'Email maksimal 150 karakter.',
            'admin_fee.required' => 'Biaya admin harus angka bulat 0 sampai 1.000.000.',
            'admin_fee.integer' => 'Biaya admin harus angka bulat 0 sampai 1.000.000.',
            'admin_fee.min' => 'Biaya admin harus angka bulat 0 sampai 1.000.000.',
            'admin_fee.max' => 'Biaya admin harus angka bulat 0 sampai 1.000.000.',
            'featured_course_id.integer' => 'Pilih kursus yang sudah terbit.',
            'featured_course_id.exists' => 'Pilih kursus yang sudah terbit.',
            'lead_magnet_title.required_with' => 'Judul video gratis wajib diisi.',
            'lead_magnet_title.max' => 'Judul video gratis maksimal 150 karakter.',
            'lead_magnet_description.max' => 'Deskripsi maksimal 5.000 karakter.',
            'lead_magnet_youtube_url.url' => self::LEAD_MAGNET_URL_MESSAGE,
            'lead_magnet_youtube_url.starts_with' => self::LEAD_MAGNET_URL_MESSAGE,
            'lead_magnet_youtube_url.max' => self::LEAD_MAGNET_URL_MESSAGE,
            'ga4_measurement_id.regex' => 'ID Google Analytics harus diawali G-.',
            'meta_pixel_id.regex' => 'ID Meta Pixel hanya berisi angka.',
            ...$links,
            ...UploadRules::imageMessages('logo_upload'),
        ];
    }

    public function settingValues(): array
    {
        return collect($this->validated())->except(['logo_upload', 'remove_logo'])->all();
    }
}
