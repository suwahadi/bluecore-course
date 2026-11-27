<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\Course;
use App\Models\Page;
use App\Services\Media\FileCleanup;
use App\Services\Media\ImageService;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SettingController extends Controller
{
    public const LOGO_WIDTH = 400;

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => Settings::all(),
            'publishedCourses' => Course::query()->where('status', CourseStatus::Published)->orderBy('title')->pluck('title', 'id')->all(),
            'landingPages' => Page::query()->landing()->orderBy('title')->get(['id', 'title', 'slug', 'youtube_url', 'is_published'])
                ->filter(fn (Page $page) => $page->videoId() !== null)
                ->mapWithKeys(fn (Page $page) => [$page->id => $page->title.' (/'.$page->slug.($page->is_published ? '' : ', draf').')'])
                ->all(),
        ]);
    }

    public function update(SettingsRequest $request, ImageService $images): RedirectResponse
    {
        $values = $request->settingValues();
        $currentLogo = Settings::get('logo');
        $newLogo = $request->hasFile('logo_upload') ? $images->storeWebp($request->file('logo_upload'), 'site', self::LOGO_WIDTH) : null;

        if ($newLogo !== null || ($request->boolean('remove_logo') && $currentLogo !== null)) {
            $values['logo'] = $newLogo;
        }

        $changedKeys = array_keys(array_filter($values, fn ($value, string $key) => (string) Settings::get($key) !== (string) $value, ARRAY_FILTER_USE_BOTH));

        FileCleanup::onFailure('public', $newLogo, fn () => DB::transaction(function () use ($values, $currentLogo) {
            if (array_key_exists('logo', $values)) {
                FileCleanup::afterCommit('public', $currentLogo);
            }

            Settings::set($values);
        }));

        Log::info('Admin updated settings', ['admin_id' => $request->user()->id, 'keys' => $changedKeys]);

        return back()->with('status', 'Pengaturan disimpan.');
    }
}
