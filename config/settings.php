<?php

$httpsUrl = ['nullable', 'url', 'starts_with:https://', 'max:255'];

return [
    'site_name' => [
        'default' => 'BlueCore Course',
        'rules' => ['required', 'string', 'max:60'],
    ],
    'site_slogan' => [
        'default' => 'Budidaya ikan dalam galon, langkah demi langkah.',
        'rules' => ['nullable', 'string', 'max:120'],
    ],
    'site_description' => [
        'default' => 'Kursus video berbahasa Indonesia tentang budidaya ikan dalam galon: menyiapkan galon, memilih benih, pakan, merawat air, sampai panen dan menjualnya. Materi dipecah per video yang diputar berurutan. Bayar sekali, akses selamanya.',
        'rules' => ['nullable', 'string', 'max:300'],
    ],
    'logo' => [
        'default' => null,
        'rules' => ['nullable', 'string', 'max:255'],
    ],
    'address' => [
        'default' => 'Bogor',
        'rules' => ['nullable', 'string', 'max:200'],
    ],
    'phone' => [
        'default' => null,
        'rules' => ['nullable', 'string', 'max:30'],
    ],
    'whatsapp' => [
        'default' => '6281818182878',
        'rules' => ['nullable', 'regex:/^62[0-9]{8,13}$/'],
    ],
    'email' => [
        'default' => 'support@bluecore.co.id',
        'rules' => ['nullable', 'email', 'max:150'],
    ],
    'instagram_url' => [
        'default' => 'https://instagram.com/bluecore.official',
        'rules' => $httpsUrl,
    ],
    'youtube_url' => [
        'default' => 'https://youtube.com/@BlueCore.official',
        'rules' => $httpsUrl,
    ],
    'tiktok_url' => [
        'default' => 'https://tiktok.com/@bluecore.id',
        'rules' => $httpsUrl,
    ],
    'shopee_url' => [
        'default' => null,
        'rules' => $httpsUrl,
    ],
    'tokopedia_url' => [
        'default' => null,
        'rules' => $httpsUrl,
    ],
    'tiktokshop_url' => [
        'default' => null,
        'rules' => $httpsUrl,
    ],
    'admin_fee' => [
        'default' => 0,
        'rules' => ['required', 'integer', 'min:0', 'max:1000000'],
    ],
    'featured_course_id' => [
        'default' => null,
        'rules' => ['nullable', 'integer', 'exists:courses,id'],
    ],
    'lead_magnet_title' => [
        'default' => null,
        'rules' => ['nullable', 'string', 'max:150', 'required_with:lead_magnet_youtube_url'],
    ],
    'lead_magnet_description' => [
        'default' => null,
        'rules' => ['nullable', 'string', 'max:5000'],
    ],
    'lead_magnet_youtube_url' => [
        'default' => null,
        'rules' => $httpsUrl,
    ],
    'ga4_measurement_id' => [
        'default' => null,
        'rules' => ['nullable', 'regex:/^G-[A-Z0-9]{4,12}$/'],
    ],
    'meta_pixel_id' => [
        'default' => null,
        'rules' => ['nullable', 'regex:/^[0-9]{10,20}$/'],
    ],
];
