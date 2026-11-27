<?php

use App\Models\Page;
use Database\Seeders\LegalPages;
use Database\Seeders\PageSeeder;

it('creates the two system pages with the appendix drafts', function () {
    $this->seed(PageSeeder::class);

    $terms = Page::systemPage('terms');
    $privacy = Page::systemPage('privacy');

    expect($terms)->title->toBe('Syarat dan Ketentuan')->slug->toBe('terms-and-condition')
        ->is_published->toBeTrue()->show_in_footer->toBeTrue()->sort_order->toBe(1)
        ->and($terms->content)->toBe(LegalPages::TERMS)->toStartWith('Berlaku sejak: [tanggal situs live]')
        ->and($privacy)->title->toBe('Kebijakan Privasi')->slug->toBe('privacy-policy')->sort_order->toBe(2)
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

it('adds a published FAQ and a draft about page', function () {
    $this->seed(PageSeeder::class);

    $faq = Page::systemPage('faq');
    $about = Page::systemPage('about');

    expect($faq)->title->toBe('FAQ')->slug->toBe('faq')->is_published->toBeTrue()->show_in_footer->toBeTrue()
        ->and($faq->faqItems())->toHaveCount(11)
        ->and($faq->faqItems()[0]['question'])->toBe('Apakah kursus ini cocok untuk pemula?')
        ->and($faq->content)->not->toContain('KONFIRMASI')
        ->and($about)->title->toBe('Tentang Bluecore')->slug->toBe('tentang-bluecore')->is_published->toBeFalse();
});
