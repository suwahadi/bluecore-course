<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $systemPages = [
            ['terms', 'Syarat dan Ketentuan', 'terms-and-condition', LegalPages::TERMS, 1, true],
            ['privacy', 'Kebijakan Privasi', 'privacy-policy', LegalPages::PRIVACY, 2, true],
            ['faq', 'FAQ', 'faq', InfoPages::FAQ, 3, true],
            ['about', 'Tentang Bluecore', 'tentang-bluecore', InfoPages::ABOUT, 4, false],
        ];

        foreach ($systemPages as [$key, $title, $slug, $content, $position, $published]) {
            Page::query()->firstOrCreate(['system_key' => $key], [
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'is_published' => $published,
                'show_in_footer' => true,
                'sort_order' => $position,
            ]);
        }
    }
}
