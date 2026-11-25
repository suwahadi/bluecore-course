<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

// Runs a copy of public/index.php against a stub application, so the hosting
// layout of PRD 17.2 (~/domains/<host>/public_html + ~/apps/<host>) is proven
// without booting a second Laravel.

beforeEach(function () {
    $this->root = storage_path('framework/testing/entry-'.Str::lower(Str::random(8)));
});

afterEach(function () {
    File::deleteDirectory($this->root);
});

function stubApplication(string $base): void
{
    File::ensureDirectoryExists($base.'/vendor');
    File::ensureDirectoryExists($base.'/bootstrap');
    File::put($base.'/vendor/autoload.php', implode("\n", [
        '<?php',
        'namespace Illuminate\Http { class Request { public static function capture() { return new self; } } }',
        'namespace Illuminate\Foundation { class Application {} }',
    ]));
    File::put($base.'/bootstrap/app.php', implode("\n", [
        '<?php',
        'return new class {',
        '    public function usePublicPath($path) { echo "PUBLIC=".realpath($path).PHP_EOL; return $this; }',
        '    public function handleRequest($request) { echo "HANDLED".PHP_EOL; }',
        '};',
    ]));
}

function runEntry(string $publicDir): string
{
    File::ensureDirectoryExists($publicDir);
    File::copy(public_path('index.php'), $publicDir.'/index.php');

    return Process::path($publicDir)->run([PHP_BINARY, 'index.php'])->output();
}

it('boots from the shared hosting layout', function () {
    stubApplication($this->root.'/apps/contoh.test');
    $public = $this->root.'/domains/contoh.test/public_html';

    $output = runEntry($public);

    expect($output)->toContain('PUBLIC='.realpath($public))->toContain('HANDLED');
});

it('reads maintenance mode from the application folder', function () {
    stubApplication($this->root.'/apps/contoh.test');
    File::ensureDirectoryExists($this->root.'/apps/contoh.test/storage/framework');
    File::put($this->root.'/apps/contoh.test/storage/framework/maintenance.php', '<?php echo "MAINTENANCE"; exit;');

    $output = runEntry($this->root.'/domains/contoh.test/public_html');

    expect($output)->toContain('MAINTENANCE')->not->toContain('HANDLED');
});

it('still boots from the usual public folder', function () {
    stubApplication($this->root.'/project');
    $public = $this->root.'/project/public';

    $output = runEntry($public);

    expect($output)->toContain('PUBLIC='.realpath($public))->toContain('HANDLED');
});
