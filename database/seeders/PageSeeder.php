<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public const FOOTER_ORDER = ['about' => 1, 'faq' => 2, 'privacy' => 3, 'terms' => 4];

    public function run(): void
    {
        $systemPages = [
            ['terms', 'Syarat dan Ketentuan', 'terms-and-condition', LegalPages::TERMS],
            ['privacy', 'Kebijakan Privasi', 'privacy-policy', LegalPages::PRIVACY],
            ['faq', 'FAQ', 'faq', InfoPages::FAQ],
            ['about', 'Tentang Bluecore', 'about', InfoPages::ABOUT],
        ];

        foreach ($systemPages as [$key, $title, $slug, $content]) {
            Page::query()->firstOrCreate(['system_key' => $key], [
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'is_published' => true,
                'show_in_footer' => true,
                'sort_order' => self::FOOTER_ORDER[$key],
            ]);
        }
    }
}
