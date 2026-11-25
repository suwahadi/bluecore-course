<?php

use Illuminate\Support\Facades\File;

// Aturan 1 di AGENTS.md: kode aplikasi tidak boleh berisi komentar apa pun.
// Folder tests/ dan .env.example dikecualikan, jadi tidak ikut dipindai.

function hygieneTargets(): array
{
    return [
        'app', 'bootstrap', 'config', 'database', 'routes', 'lang', 'resources',
        'public/index.php', 'public/.htaccess', 'artisan', 'vite.config.js', 'phpunit.xml',
    ];
}

function hygieneFiles(string $path): array
{
    $full = base_path($path);

    if (is_file($full)) {
        return [$full];
    }

    if (! is_dir($full)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        $name = str_replace('\\', '/', $file->getPathname());

        if (str_contains($name, 'bootstrap/cache/')) {
            continue;
        }

        $files[] = $name;
    }

    return $files;
}

function scriptCommentLines(string $code): array
{
    $lines = [];

    foreach (preg_split('/\R/', $code) as $number => $line) {
        // Isi string (pola glob, URL) bukan komentar, jadi dibuang sebelum diperiksa.
        $line = preg_replace('/([\'"`])(?:\\\\.|(?!\1).)*\1/', '', $line);

        if (preg_match('#^\s*//#', $line) || str_contains($line, '/*') || preg_match('#\s//#', $line)) {
            $lines[] = $number + 1;
        }
    }

    return $lines;
}

function phpCommentLines(string $code): array
{
    $lines = [];

    foreach (token_get_all($code) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            $lines[] = $token[2];
        }
    }

    return $lines;
}

function commentViolations(): array
{
    $violations = [];

    foreach (hygieneTargets() as $target) {
        foreach (hygieneFiles($target) as $file) {
            $code = file_get_contents($file);
            $relative = ltrim(str_replace(str_replace('\\', '/', base_path()), '', str_replace('\\', '/', $file)), '/');
            $base = basename($file);

            if (str_ends_with($file, '.blade.php') || str_ends_with($file, '.xml') || str_ends_with($file, '.html')) {
                foreach (['{{--', '<!--'] as $marker) {
                    if (str_contains($code, $marker)) {
                        $violations[] = $relative.': '.$marker;
                    }
                }

                // Blok PHP biasa dan @php ... @endphp diperiksa dengan tokenizer.
                preg_match_all('/@php\b(.*?)@endphp/s', $code, $phpBlocks);

                foreach ([$code, ...array_map(fn (string $block) => '<?php '.$block, $phpBlocks[1])] as $php) {
                    foreach (phpCommentLines($php) as $line) {
                        $violations[] = $relative.': PHP comment near line '.$line;
                    }
                }

                // Isi <script> dan <style> diperiksa dengan aturan yang sama seperti file JS dan CSS.
                preg_match_all('#<(script|style)\b[^>]*>(.*?)</\1>#si', $code, $inline);

                foreach ($inline[2] as $block) {
                    if (scriptCommentLines($block) !== []) {
                        $violations[] = $relative.': comment in script or style';
                    }
                }

                continue;
            }

            if (str_ends_with($file, '.php') || $base === 'artisan') {
                foreach (phpCommentLines($code) as $line) {
                    $violations[] = $relative.':'.$line;
                }

                continue;
            }

            if (str_ends_with($file, '.js') || str_ends_with($file, '.css')) {
                foreach (scriptCommentLines($code) as $line) {
                    $violations[] = $relative.':'.$line;
                }

                continue;
            }

            if ($base === '.htaccess') {
                foreach (preg_split('/\R/', $code) as $number => $line) {
                    if (preg_match('/^\s*#/', $line)) {
                        $violations[] = $relative.':'.($number + 1);
                    }
                }
            }
        }
    }

    return $violations;
}

it('keeps application code free of comments', function () {
    expect(commentViolations())->toBe([]);
});

it('finds comments hidden in Blade PHP, script and style blocks', function () {
    $dir = resource_path('views/_hygiene_probe');
    File::ensureDirectoryExists($dir);

    try {
        File::put($dir.'/php.blade.php', "@php\n    // catatan\n    \$a = 1;\n@endphp");
        File::put($dir.'/script.blade.php', "<script>\n    // catatan\n    let a = 1;\n</script>");
        File::put($dir.'/style.blade.php', "<style>\n    /* catatan */\n    a { color: red; }\n</style>");
        File::put($dir.'/clean.blade.php', "<script>\n    fetch('https://contoh.id/a');\n</script>\n<style>\n    a { background: url('https://contoh.id/b.png'); }\n</style>");

        $found = array_values(array_filter(commentViolations(), fn (string $violation) => str_contains($violation, '_hygiene_probe')));

        expect($found)->toHaveCount(3)
            ->and(implode(' ', $found))->toContain('php.blade.php')->toContain('script.blade.php')->toContain('style.blade.php')->not->toContain('clean.blade.php');
    } finally {
        File::deleteDirectory($dir);
    }
});
