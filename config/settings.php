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
        'default' => 'Budidaya ikan dalam galon, langkah demi langkah.',
        'rules' => ['nullable', 'string', 'max:120'],
    ],
    'site_description' => [
        'type' => 'string',
        'default' => 'Kursus video berbahasa Indonesia tentang budidaya ikan dalam galon: menyiapkan galon, memilih benih, pakan, merawat air, sampai panen dan menjualnya. Materi dipecah per video yang diputar berurutan. Bayar sekali, akses selamanya.',
        'rules' => ['nullable', 'string', 'max:300'],
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
