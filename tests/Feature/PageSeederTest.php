<?php

use App\Models\Page;
use Database\Seeders\LegalPages;
use Database\Seeders\PageSeeder;

it('creates the two system pages with the appendix drafts', function () {
    $this->seed(PageSeeder::class);

    $terms = Page::systemPage('terms');
    $privacy = Page::systemPage('privacy');

    expect($terms)->title->toBe('Syarat & Ketentuan')->slug->toBe('terms-and-condition')
        ->is_published->toBeTrue()->show_in_footer->toBeTrue()->sort_order->toBe(4)
        ->and($terms->content)->toBe(LegalPages::TERMS)->toStartWith('Berlaku sejak: [tanggal situs live]')
        ->and($privacy)->title->toBe('Kebijakan Privasi')->slug->toBe('privacy-policy')->sort_order->toBe(3)
        ->and($privacy->content)->toContain('## Kontak');
});

it('never overwrites pages the admin already changed', function () {
    $this->seed(PageSeeder::class);
    Page::systemPage('terms')->update(['title' => 'Syarat Layanan', 'content' => 'Isi baru.']);

    $this->seed(PageSeeder::class);

    expect(Page::query()->count())->toBe(4)
        ->and(Page::systemPage('terms'))->title->toBe('Syarat Layanan')->content->toBe('Isi baru.');
});

it('runs from the default database seeder', function () {
    $this->seed();

    expect(Page::systemPage('privacy'))->not->toBeNull();
});

it('adds a published FAQ and a published about page', function () {
    $this->seed(PageSeeder::class);

    $faq = Page::systemPage('faq');
    $about = Page::systemPage('about');

    expect($faq)->title->toBe('FAQ')->slug->toBe('faq')->is_published->toBeTrue()->show_in_footer->toBeTrue()
        ->and($faq->faqItems())->toHaveCount(11)
        ->and($faq->faqItems()[0]['question'])->toBe('Apakah kursus ini cocok untuk pemula?')
        ->and($faq->content)->not->toContain('KONFIRMASI')
        ->and($about)->title->toBe('Tentang Bluecore')->slug->toBe('about')->is_published->toBeTrue()->show_in_footer->toBeTrue()
        ->and(substr_count($about->content, "\n\n"))->toBe(2)
        ->and($about->content)->toStartWith('Lorem ipsum dolor sit amet');
});

it('publishes the about page at /about on an existing site and orders the footer pages', function () {
    $this->seed(PageSeeder::class);
    Page::systemPage('about')->update(['slug' => 'tentang-bluecore', 'is_published' => false, 'content' => 'Lama.']);

    $this->seed(Database\Seeders\AboutPageSeeder::class);
    $this->seed(Database\Seeders\AboutPageSeeder::class);

    expect(Page::systemPage('about'))->slug->toBe('about')->is_published->toBeTrue()->show_in_footer->toBeTrue()
        ->content->toBe(Database\Seeders\InfoPages::ABOUT)
        ->and(Page::query()->whereNotNull('system_key')->orderBy('sort_order')->pluck('system_key')->all())->toBe(['about', 'faq', 'privacy', 'terms'])
        ->and(Page::query()->count())->toBe(4);
    $this->get('/about')->assertOk()->assertSee('<h1>Tentang Bluecore</h1>', false)->assertSeeText('Lorem ipsum dolor sit amet');
});
