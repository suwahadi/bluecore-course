<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        Page::query()->updateOrCreate(['system_key' => 'about'], [
            'title' => 'Tentang Bluecore',
            'slug' => 'about',
            'content' => InfoPages::ABOUT,
            'is_published' => true,
            'show_in_footer' => true,
        ]);

        foreach (PageSeeder::FOOTER_ORDER as $key => $position) {
            Page::query()->where('system_key', $key)->get()->each->update(['sort_order' => $position]);
        }
    }
}
