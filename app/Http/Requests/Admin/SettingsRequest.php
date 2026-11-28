<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourseStatus;
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

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = collect(config('settings'))->except('logo')->map(fn (array $definition) => $definition['rules'])->all();

        $rules['featured_course_id'] = ['nullable', 'integer', Rule::exists('courses', 'id')->where('status', CourseStatus::Published->value)];
        $rules['free_video_page_id'] = ['nullable', 'integer', Rule::exists('pages', 'id')->whereNotNull('youtube_url')];
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
            'company_name.max' => 'Nama perusahaan maksimal 120 karakter.',
            'footer_disclaimer.max' => 'Disclaimer maksimal 600 karakter.',
            'hero_eyebrow.max' => 'Teks kecil hero maksimal 60 karakter.',
            'hero_title.max' => 'Judul hero maksimal 120 karakter.',
            'hero_subtitle.max' => 'Subjudul hero maksimal 200 karakter.',
            'home_courses_text.max' => 'Teks di bawah daftar kursus maksimal 2.000 karakter.',
            'testimonials_title.max' => 'Judul testimoni maksimal 120 karakter.',
            'cta_title.max' => 'Judul ajakan penutup maksimal 120 karakter.',
            'cta_text.max' => 'Teks ajakan penutup maksimal 300 karakter.',
            'checkout_guarantee.max' => 'Garansi maksimal 150 karakter.',
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
            'free_video_page_id.integer' => 'Pilih halaman landing yang punya video.',
            'free_video_page_id.exists' => 'Pilih halaman landing yang punya video.',
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
