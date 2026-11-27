<?php

use App\Enums\SignupSource;
use App\Models\Page;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

function runFreeVideoMigration(): void
{
    (require database_path('migrations/2026_11_27_000002_move_free_video_to_page.php'))->up();
}

function legacySetting(string $key, ?string $value): void
{
    DB::table('settings')->insert(['key' => $key, 'value' => json_encode($value), 'created_at' => now(), 'updated_at' => now()]);
}

it('turns the free video settings into a locked landing page', function () {
    legacySetting('lead_magnet_title', 'Cara menyiapkan galon');
    legacySetting('lead_magnet_description', "Paragraf satu.\n\nParagraf dua.");
    legacySetting('lead_magnet_youtube_url', 'https://youtu.be/M7lc1UVf-VE');
    $fromVideo = User::factory()->create(['signup_source' => SignupSource::FreeVideo]);
    $direct = User::factory()->create();

    runFreeVideoMigration();

    $page = Page::firstWhere('slug', 'free-video');
    expect($page)->not->toBeNull()
        ->and($page->title)->toBe('Cara menyiapkan galon')
        ->and($page->content)->toBe("Paragraf satu.\n\nParagraf dua.")
        ->and($page->videoId())->toBe('M7lc1UVf-VE')
        ->and($page->requires_signup)->toBeTrue()
        ->and($page->show_in_footer)->toBeFalse()
        ->and($page->is_published)->toBeTrue()
        ->and(Settings::get('free_video_page_id'))->toBe($page->id)
        ->and($fromVideo->fresh()->signup_landing)->toBe('/free-video')
        ->and($direct->fresh()->signup_landing)->toBeNull()
        ->and(DB::table('settings')->where('key', 'like', 'lead_magnet_%')->count())->toBe(0);
});

it('names the landing page Video gratis when no title was saved', function () {
    legacySetting('lead_magnet_youtube_url', 'https://youtu.be/M7lc1UVf-VE');

    runFreeVideoMigration();

    expect(Page::firstWhere('slug', 'free-video')?->title)->toBe('Video gratis');
});

it('only drops the old keys when no free video was set', function () {
    legacySetting('lead_magnet_title', 'Judul lama');
    legacySetting('lead_magnet_youtube_url', null);

    runFreeVideoMigration();

    expect(Page::query()->where('slug', 'free-video')->exists())->toBeFalse()
        ->and(Settings::get('free_video_page_id'))->toBeNull()
        ->and(DB::table('settings')->where('key', 'like', 'lead_magnet_%')->count())->toBe(0);
});

it('shortens a long old title to fit the page title and keeps /free-video working', function () {
    legacySetting('lead_magnet_title', str_repeat('Lele ', 30));
    legacySetting('lead_magnet_youtube_url', 'https://youtu.be/M7lc1UVf-VE');

    runFreeVideoMigration();

    expect(mb_strlen(Page::firstWhere('slug', 'free-video')->title))->toBeLessThanOrEqual(120);
    $this->get('/free-video')->assertOk()->assertSeeText('Satu Langkah Lagi, Lalu Langsung Tonton');
});
