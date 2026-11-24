<?php

use App\Services\Media\ImageService;
use App\Support\UploadRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

function jpegUpload(int $width, int $height, string $exif = ''): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, intdiv($width, 2), $height, imagecolorallocate($image, 4, 76, 148));
    ob_start();
    imagejpeg($image, null, 90);
    $bytes = ob_get_clean();

    if ($exif !== '') {
        $payload = "Exif\x00\x00".$exif;
        $bytes = substr($bytes, 0, 2)."\xFF\xE1".pack('n', strlen($payload) + 2).$payload.substr($bytes, 2);
    }

    $path = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, 'foto.jpg', 'image/jpeg', null, true);
}

function pngUpload(int $width, int $height): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    $path = tempnam(sys_get_temp_dir(), 'img');
    imagepng($image, $path);

    return new UploadedFile($path, 'logo.png', 'image/png', null, true);
}

function bytesUpload(string $bytes, string $name, string $mime): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'upl');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, $name, $mime, null, true);
}

function pngHeaderClaiming(int $width, int $height): string
{
    $ihdr = 'IHDR'.pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);

    return "\x89PNG\r\n\x1a\n".pack('N', 13).$ihdr.pack('N', crc32($ihdr)).str_repeat("\x00", 64);
}

function orientationExif(int $orientation): string
{
    return "MM\x00\x2A\x00\x00\x00\x08\x00\x01\x01\x12\x00\x03\x00\x00\x00\x01".pack('n', $orientation)."\x00\x00\x00\x00\x00\x00";
}

function imageErrors(UploadedFile $file): array
{
    return Validator::make(['image' => $file], ['image' => UploadRules::image()], UploadRules::imageMessages('image'))->errors()->get('image');
}

beforeEach(fn () => Storage::fake('public'));

it('stores a large JPEG as a 1200 pixel wide WebP with a random name', function () {
    $path = app(ImageService::class)->storeWebp(jpegUpload(2000, 1500), 'courses', 1200);

    expect($path)->toMatch('#^courses/[a-z0-9]{14}\.webp$#');
    $info = getimagesizefromstring(Storage::disk('public')->get($path));
    expect($info[0])->toBe(1200)->and($info[1])->toBe(900)->and($info['mime'])->toBe('image/webp');
});

it('keeps small images at their own size', function () {
    $path = app(ImageService::class)->storeWebp(pngUpload(300, 200), 'site', 400);

    expect(getimagesizefromstring(Storage::disk('public')->get($path))[0])->toBe(300);
});

it('drops metadata while re-encoding', function () {
    $path = app(ImageService::class)->storeWebp(jpegUpload(400, 300, orientationExif(1).'BLUECORE-HIDDEN-PAYLOAD'), 'courses', 1200);

    expect(Storage::disk('public')->get($path))->not->toContain('BLUECORE-HIDDEN-PAYLOAD');
});

it('turns photos upright from their EXIF orientation', function () {
    $path = app(ImageService::class)->storeWebp(jpegUpload(400, 200, orientationExif(6)), 'courses', 1200);

    $info = getimagesizefromstring(Storage::disk('public')->get($path));
    expect([$info[0], $info[1]])->toBe([200, 400]);
});

it('accepts real JPEG, PNG and WebP images', function () {
    expect(imageErrors(jpegUpload(400, 300)))->toBe([])
        ->and(imageErrors(pngUpload(400, 300)))->toBe([]);
});

it('rejects files that only pretend to be images', function (UploadedFile $file) {
    expect(imageErrors($file))->not->toBe([]);
})->with([
    'php named jpg' => fn () => bytesUpload('<?php echo "bukan gambar"; ?>', 'foto.jpg', 'image/jpeg'),
    'svg with script' => fn () => bytesUpload('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'logo.svg', 'image/svg+xml'),
    'gif' => fn () => bytesUpload(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 'anim.gif', 'image/gif'),
    'tiny png' => fn () => pngUpload(100, 100),
]);

it('rejects a decompression bomb before decoding it', function () {
    $errors = imageErrors(bytesUpload(pngHeaderClaiming(10000, 10000), 'bom.png', 'image/png'));

    expect($errors)->not->toBe([]);
});

it('says the file is not a valid image when it cannot be read', function () {
    $errors = Validator::make(
        ['image' => bytesUpload(pngHeaderClaiming(300, 300), 'rusak.png', 'image/png')],
        ['image' => [new App\Rules\RealImage]],
    )->errors()->get('image');

    expect($errors)->toBe(['File bukan gambar yang valid.']);
});
