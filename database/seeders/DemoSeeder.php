<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\Page;
use App\Models\Testimonial;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    public const VIDEO_ID = 'M7lc1UVf-VE';

    private const PDF = "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n2 0 obj << /Type /Pages /Kids [] /Count 0 >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n";

    private const CATEGORIES = [['Lele', 1], ['Nila', 2], ['Ikan Hias', 3]];

    private const COURSES = [
        ['Budidaya Lele dalam Galon', 'Lele', 149000, 199000, true, ['Menyiapkan galon', 'Memilih benih', 'Pakan harian', 'Merawat air', 'Panen pertama']],
        ['Nila Merah untuk Pemula', 'Nila', 129000, null, true, ['Kenali nila merah', 'Galon dan aerasi', 'Pakan dan jadwal', 'Panen dan sortir']],
        ['Cupang Hias di Rumah', 'Ikan Hias', 99000, 149000, true, ['Memilih indukan', 'Wadah dan air', 'Pembenihan']],
        ['Menjual Hasil Panen', 'Lele', 79000, null, true, ['Menghitung modal', 'Mencari pembeli', 'Mengemas ikan hidup']],
        ['Lele Bioflok Lanjutan', 'Lele', 199000, 249000, false, ['Dasar bioflok', 'Mengatur probiotik']],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoSeeder tidak dijalankan di production.');

            return;
        }

        $categories = collect(self::CATEGORIES)->mapWithKeys(fn (array $row) => [
            $row[0] => Category::query()->firstOrCreate(['name' => $row[0]], ['sort_order' => $row[1]]),
        ]);

        foreach (self::COURSES as $index => [$title, $category, $price, $compareAt, $published, $segments]) {
            $this->course($index, $title, $categories[$category], $price, $compareAt, $published, $segments);
        }

        if (Testimonial::query()->doesntExist()) {
            foreach (range(1, 4) as $position) {
                Testimonial::query()->create([
                    'name' => '[Nama pembeli]',
                    'note' => '[Kota atau kursus yang dibeli]',
                    'quote' => '[Tulis testimoni asli dari pembeli di sini]',
                    'sort_order' => $position,
                ]);
            }
        }

        if (blank(Settings::get('free_video_page_id'))) {
            $landing = Page::query()->firstOrCreate(['slug' => 'free-video'], [
                'title' => '[Judul video gratis]',
                'content' => "[Tulis deskripsi video gratis di sini.]\n\n[Pakai Markdown untuk judul, daftar, dan tautan.]",
                'youtube_url' => 'https://youtu.be/'.self::VIDEO_ID,
                'show_in_footer' => false,
            ]);
            Settings::set(['free_video_page_id' => $landing->id]);
        }
    }

    private function course(int $index, string $title, Category $category, int $price, ?int $compareAt, bool $published, array $segments): void
    {
        $course = Course::query()->firstOrCreate(['title' => $title], [
            'category_id' => $category->id,
            'short_description' => '[Ringkasan kursus contoh. Ganti dengan ringkasan asli.]',
            'description' => "[Deskripsi kursus contoh.]\n\n[Ganti dengan deskripsi asli sebelum situs live.]",
            'price' => $price,
            'compare_at_price' => $compareAt,
            'status' => $published ? CourseStatus::Published : CourseStatus::Draft,
            'published_at' => $published ? now()->subDays(count(self::COURSES) - $index) : null,
        ]);

        if (! $course->wasRecentlyCreated) {
            return;
        }

        foreach ($segments as $position => $segmentTitle) {
            $course->segments()->create([
                'title' => $segmentTitle,
                'youtube_video_id' => self::VIDEO_ID,
                'duration_seconds' => 240 + $position * 90,
                'sort_order' => $position + 1,
            ]);
        }

        $path = 'materials/demo-'.$course->slug.'.pdf';
        Storage::disk('local')->put($path, self::PDF);

        $course->materials()->create([
            'title' => 'Panduan '.$title,
            'file_path' => $path,
            'original_name' => 'panduan-'.$course->slug.'.pdf',
            'file_size' => strlen(self::PDF),
            'sort_order' => 1,
        ]);
    }
}
