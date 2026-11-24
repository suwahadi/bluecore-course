<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $systemPages = [
            ['terms', 'Syarat dan Ketentuan', 'terms-and-condition', LegalPages::TERMS, 1],
            ['privacy', 'Kebijakan Privasi', 'privacy-policy', LegalPages::PRIVACY, 2],
        ];

        foreach ($systemPages as [$key, $title, $slug, $content, $position]) {
            Page::query()->firstOrCreate(['system_key' => $key], [
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'is_published' => true,
                'show_in_footer' => true,
                'sort_order' => $position,
            ]);
        }
    }
}
