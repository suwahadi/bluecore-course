<?php

use App\Http\Controllers\Dev\MailPreviewController;
use App\Support\SiteLogo;
use Illuminate\Mail\Markdown;

function previewHtml(string $key): string
{
    return (string) MailPreviewController::previews()[$key]['message']()->render();
}

function previewText(string $key): string
{
    $message = MailPreviewController::previews()[$key]['message']();

    return (string) app(Markdown::class)->renderText($message->markdown, $message->data());
}

it('dresses every email in the Bluecore brand', function (string $key) {
    $html = previewHtml($key);

    expect($html)
        ->toMatch('/background-color:\s*#044C94/i')
        ->toContain('src="'.SiteLogo::emailUrl().'"')
        ->toContain('Bluecore Course')
        ->toMatch('/background-color:\s*#C64B00/i')
        ->toContain('support@bluecore.co.id')
        ->toContain('https://wa.me/6281818182878?text='.rawurlencode('Halo Bluecore Course, saya ingin bertanya.'))
        ->toMatch('/Salam,<br\s*\/?>\s*Bluecore Course/')
        ->not->toContain('laravel.com')
        ->not->toContain('Bogor');
})->with(['verify', 'reset', 'order-created', 'payment-succeeded', 'order-expired', 'access-granted', 'admin-order-paid']);

it('lays out the order breakdown as a table with the total emphasised', function () {
    $html = previewHtml('order-created');

    expect($html)->toContain('class="summary"')
        ->toMatch('/Harga kursus.*Rp 149\.000.*Biaya admin.*Rp 2\.500.*Total.*Rp 151\.500/s')
        ->toMatch('/<strong[^>]*>Rp 151\.500<\/strong>/');
    expect(previewText('order-created'))->toContain('Harga kursus: Rp 149.000')->toContain('Total: Rp 151.500')->not->toContain('<table');
});

it('shows the paid order and the admin details as tables', function () {
    expect(previewHtml('payment-succeeded'))->toMatch('/class="summary".*Kursus.*Budidaya Lele dalam Galon.*Total dibayar.*Rp 151\.500/s');
    expect(previewHtml('admin-order-paid'))->toMatch('/class="summary".*Pembeli.*Sari Lestari.*sari@contoh\.id.*Kursus.*Metode bayar.*ShopeePay.*Total.*Rp 151\.500/s');
});

it('keeps space between a summary label and its value', function () {
    preg_match('/<td class="summary-label"[^>]*style="([^"]*)"/', previewHtml('order-created'), $match);

    expect($match[1] ?? '')->toMatch('/padding:\s*10px 0;.*padding-right:\s*16px/');
});

it('uses a PNG logo that every mail app can show', function () {
    expect(previewHtml('verify'))->toContain('src="'.asset('images/logo-email.png').'"');
});
