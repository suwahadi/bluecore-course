<?php

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

                continue;
            }

            if (str_ends_with($file, '.php') || $base === 'artisan') {
                foreach (token_get_all($code) as $token) {
                    if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                        $violations[] = $relative.':'.$token[2];
                    }
                }

                continue;
            }

            if (str_ends_with($file, '.js') || str_ends_with($file, '.css')) {
                foreach (preg_split('/\R/', $code) as $number => $line) {
                    // Isi string (pola glob, URL) bukan komentar, jadi dibuang sebelum diperiksa.
                    $line = preg_replace('/([\'"`])(?:\\\\.|(?!\1).)*\1/', '', $line);

                    if (preg_match('#^\s*//#', $line) || str_contains($line, '/*') || preg_match('#\s//#', $line)) {
                        $violations[] = $relative.':'.($number + 1);
                    }
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
