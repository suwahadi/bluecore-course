<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_KEYS = ['lead_magnet_title', 'lead_magnet_description', 'lead_magnet_youtube_url'];

    private const SLUG = 'free-video';

    public function up(): void
    {
        $old = DB::table('settings')->whereIn('key', self::OLD_KEYS)->pluck('value', 'key')
            ->map(fn (?string $value) => $value === null ? null : json_decode($value, true));

        DB::transaction(function () use ($old) {
            if (filled($old['lead_magnet_youtube_url'] ?? null) && DB::table('pages')->where('slug', self::SLUG)->doesntExist()) {
                $now = now();
                $pageId = DB::table('pages')->insertGetId([
                    'title' => filled($old['lead_magnet_title'] ?? null) ? $old['lead_magnet_title'] : 'Video gratis',
                    'slug' => self::SLUG,
                    'content' => $old['lead_magnet_description'] ?? null,
                    'youtube_url' => $old['lead_magnet_youtube_url'],
                    'requires_signup' => true,
                    'is_published' => true,
                    'show_in_footer' => false,
                    'sort_order' => (int) DB::table('pages')->max('sort_order') + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('settings')->updateOrInsert(['key' => 'free_video_page_id'], ['value' => json_encode($pageId), 'created_at' => $now, 'updated_at' => $now]);
                DB::table('users')->where('signup_source', 'free_video')->whereNull('signup_landing')->update(['signup_landing' => '/'.self::SLUG]);
            }

            DB::table('settings')->whereIn('key', self::OLD_KEYS)->delete();
        });

        Cache::forget('settings');
        app()->forgetInstance('settings.values');
    }

    public function down(): void
    {
        $pageId = DB::table('settings')->where('key', 'free_video_page_id')->value('value');
        $page = $pageId === null ? null : DB::table('pages')->find(json_decode($pageId, true));

        DB::transaction(function () use ($page) {
            if ($page !== null) {
                $now = now();
                foreach (['lead_magnet_title' => $page->title, 'lead_magnet_description' => $page->content, 'lead_magnet_youtube_url' => $page->youtube_url] as $key => $value) {
                    DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'created_at' => $now, 'updated_at' => $now]);
                }
            }

            DB::table('settings')->where('key', 'free_video_page_id')->delete();
        });

        Cache::forget('settings');
        app()->forgetInstance('settings.values');
    }
};
