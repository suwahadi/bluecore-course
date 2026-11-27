<?php

$httpsUrl = ['nullable', 'url', 'starts_with:https://', 'max:255'];

return [
    'site_name' => [
        'type' => 'string',
        'default' => 'BlueCore Course',
        'rules' => ['required', 'string', 'max:60'],
    ],
    'site_slogan' => [
        'type' => 'string',
        'default' => 'Mulai dari Nol, Naik Kelas Bersama Bluecore.',
        'rules' => ['nullable', 'string', 'max:120'],
    ],
    'site_description' => [
        'type' => 'string',
        'default' => 'Belajar budidaya lebih terarah melalui materi berbasis praktik, perhitungan, dan evaluasi.',
        'rules' => ['nullable', 'string', 'max:300'],
    ],
    'company_name' => [
        'type' => 'string',
        'default' => null,
        'rules' => ['nullable', 'string', 'max:120'],
    ],
    'footer_disclaimer' => [
        'type' => 'string',
        'default' => 'Hasil budidaya setiap peserta dapat berbeda, bergantung pada bibit, pakan, kualitas air, cuaca, dan perawatan. Materi bersifat edukasi dan tidak menjamin hasil panen atau keuntungan tertentu. Contoh hitungan bersifat ilustrasi.',
        'rules' => ['nullable', 'string', 'max:600'],
    ],
    'hero_eyebrow' => [
        'type' => 'string',
        'default' => 'Video gratis untuk pemula',
        'rules' => ['nullable', 'string', 'max:60'],
    ],
    'hero_title' => [
        'type' => 'string',
        'default' => 'Keuntungan Maksimal Sejak Panen Pertama.',
        'rules' => ['nullable', 'string', 'max:120'],
    ],
    'hero_subtitle' => [
        'type' => 'string',
        'default' => 'Hitung Perhitungan Lengkapnya di Video Gratis Ini.',
        'rules' => ['nullable', 'string', 'max:200'],
    ],
    'testimonials_title' => [
        'type' => 'string',
        'default' => 'Bukan Cuma Berani Mulai, Tapi Tahu Apa yang Harus Dilakukan.',
        'rules' => ['nullable', 'string', 'max:120'],
    ],
    'cta_title' => [
        'type' => 'string',
        'default' => 'Galon Bekas di Rumah Sudah Siap? Tinggal Kamu yang Mulai.',
        'rules' => ['nullable', 'string', 'max:120'],
    ],
    'cta_text' => [
        'type' => 'string',
        'default' => 'Tonton video gratisnya terlebih dahulu, atau langsung lihat kursus lengkap.',
        'rules' => ['nullable', 'string', 'max:300'],
    ],
    'checkout_guarantee' => [
        'type' => 'string',
        'default' => 'Garansi 7 hari uang kembali.',
        'rules' => ['nullable', 'string', 'max:150'],
    ],
    'logo' => [
        'type' => 'string',
        'default' => null,
        'rules' => ['nullable', 'string', 'max:255'],
    ],
    'address' => [
        'type' => 'string',
        'default' => 'Bogor',
        'rules' => ['nullable', 'string', 'max:200'],
    ],
    'phone' => [
        'type' => 'string',
        'default' => null,
        'rules' => ['nullable', 'string', 'max:30'],
    ],
    'whatsapp' => [
        'type' => 'string',
        'default' => '6281818182878',
        'rules' => ['nullable', 'regex:/^62[0-9]{8,13}$/'],
    ],
    'email' => [
        'type' => 'string',
        'default' => 'support@bluecore.co.id',
        'rules' => ['nullable', 'email', 'max:150'],
    ],
    'instagram_url' => [
        'type' => 'string',
        'default' => 'https://instagram.com/bluecore.official',
        'rules' => $httpsUrl,
    ],
    'youtube_url' => [
        'type' => 'string',
        'default' => 'https://youtube.com/@BlueCore.official',
        'rules' => $httpsUrl,
    ],
    'tiktok_url' => [
        'type' => 'string',
        'default' => 'https://tiktok.com/@bluecore.id',
        'rules' => $httpsUrl,
    ],
    'shopee_url' => [
        'type' => 'string',
        'default' => null,
        'rules' => $httpsUrl,
    ],
    'tokopedia_url' => [
        'type' => 'string',
        'default' => null,
        'rules' => $httpsUrl,
    ],
    'tiktokshop_url' => [
        'type' => 'string',
        'default' => null,
        'rules' => $httpsUrl,
    ],
    'admin_fee' => [
        'type' => 'int',
        'default' => 0,
        'rules' => ['required', 'integer', 'min:0', 'max:1000000'],
    ],
    'featured_course_id' => [
        'type' => 'int',
        'default' => null,
        'rules' => ['nullable', 'integer', 'exists:courses,id'],
    ],
    'free_video_page_id' => [
        'type' => 'int',
        'default' => null,
        'rules' => ['nullable', 'integer'],
    ],
    'ga4_measurement_id' => [
        'type' => 'string',
        'default' => null,
        'rules' => ['nullable', 'regex:/^G-[A-Z0-9]{4,12}$/'],
    ],
    'meta_pixel_id' => [
        'type' => 'string',
        'default' => null,
        'rules' => ['nullable', 'regex:/^[0-9]{10,20}$/'],
    ],
];
