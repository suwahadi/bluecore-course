<?php

use App\Services\Media\FileCleanup;
use App\Services\Media\PdfService;
use App\Support\UploadRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

function pdfUpload(string $bytes = "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF", string $name = 'Panduan Galon.pdf'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, $name, 'application/pdf', null, true);
}

function pdfErrors(UploadedFile $file): array
{
    return Validator::make(['file' => $file], ['file' => UploadRules::pdf()], UploadRules::pdfMessages('file'))->errors()->get('file');
}

beforeEach(fn () => Storage::fake('local'));

it('stores a PDF privately with a random name and its original details', function () {
    $stored = app(PdfService::class)->store(pdfUpload());

    expect($stored['path'])->toMatch('#^materials/[a-z0-9]{14}\.pdf$#')
        ->and($stored['original_name'])->toBe('Panduan Galon.pdf')
        ->and($stored['size'])->toBeGreaterThan(10);
    Storage::disk('local')->assertExists($stored['path']);
});

it('accepts a real PDF', function () {
    expect(pdfErrors(pdfUpload()))->toBe([]);
});

it('rejects files that are not PDFs', function (UploadedFile $file) {
    expect(pdfErrors($file))->toBe(['File harus PDF, maksimal 20 MB.']);
})->with([
    'missing header' => fn () => pdfUpload("1 0 obj << >> endobj\n%PDF-1.4"),
    'php named pdf' => fn () => pdfUpload('<?php echo 1; ?>', 'materi.pdf'),
    'empty' => fn () => pdfUpload('', 'kosong.pdf'),
]);

it('deletes old files only after the transaction commits', function () {
    Storage::disk('local')->put('materials/lama.pdf', 'x');

    try {
        DB::transaction(function () {
            FileCleanup::afterCommit('local', 'materials/lama.pdf');

            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    Storage::disk('local')->assertExists('materials/lama.pdf');

    DB::transaction(fn () => FileCleanup::afterCommit('local', 'materials/lama.pdf', null));

    Storage::disk('local')->assertMissing('materials/lama.pdf');
});

it('removes the new file when saving fails', function () {
    Storage::disk('local')->put('materials/baru.pdf', 'x');

    expect(fn () => FileCleanup::onFailure('local', 'materials/baru.pdf', fn () => throw new RuntimeException('gagal')))
        ->toThrow(RuntimeException::class);

    Storage::disk('local')->assertMissing('materials/baru.pdf');
});

it('keeps the new file when saving works', function () {
    Storage::disk('local')->put('materials/baru.pdf', 'x');

    expect(FileCleanup::onFailure('local', 'materials/baru.pdf', fn () => 'tersimpan'))->toBe('tersimpan');

    Storage::disk('local')->assertExists('materials/baru.pdf');
});

it('stops the web server from running scripts in the upload folder', function () {
    $htaccess = storage_path('app/public/.htaccess');

    expect(file_exists($htaccess))->toBeTrue()
        ->and(file_get_contents($htaccess))->toContain('Options -ExecCGI -Indexes')->toContain('Require all denied')->not->toContain('#')
        ->and(trim(shell_exec('git ls-files storage/app/public/.htaccess') ?? ''))->toBe('storage/app/public/.htaccess');
});
