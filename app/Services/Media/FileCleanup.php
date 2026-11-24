<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FileCleanup
{
    public static function afterCommit(string $disk, ?string ...$paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            DB::afterCommit(fn () => Storage::disk($disk)->delete($paths));
        }
    }

    public static function onFailure(string $disk, string $path, callable $work): mixed
    {
        try {
            return $work();
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }
}
