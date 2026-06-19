<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ProctoringImageUrl
{
    /**
     * Resolve a stored proctoring image path or absolute URL for use in img src.
     */
    public static function resolve(?string $storedPath): ?string
    {
        if ($storedPath === null || trim($storedPath) === '') {
            return null;
        }

        $storedPath = trim($storedPath);
        if (str_starts_with($storedPath, 'http://') || str_starts_with($storedPath, 'https://')) {
            return $storedPath;
        }

        $relativePath = ltrim($storedPath, '/');
        if ($relativePath === '') {
            return null;
        }

        if (self::publicStoragePathExists($relativePath)) {
            return asset('storage/' . $relativePath);
        }

        if (Storage::disk('public')->exists($relativePath)) {
            return route('dashboard.proctoring-media', ['path' => $relativePath]);
        }

        return null;
    }

    private static function publicStoragePathExists(string $relativePath): bool
    {
        $publicRoot = public_path('storage');
        if (! is_dir($publicRoot) && ! is_link($publicRoot)) {
            return false;
        }

        return is_file($publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    }
}
