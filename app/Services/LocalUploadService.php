<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class LocalUploadService
{
    /** Store a public image; returns a URL suitable for img src (e.g. /storage/...). */
    public static function storePublicImage(UploadedFile $file, string $directory = 'uploads/images'): ?string
    {
        $path = $file->store($directory, 'public');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * @return array{path: string, url: string}|null
     */
    public static function storePublicFile(UploadedFile $file, string $directory = 'uploads/files'): ?array
    {
        $path = $file->store($directory, 'public');
        if (! $path) {
            return null;
        }

        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    public static function deletePublicPath(?string $pathOrUrl): void
    {
        if ($pathOrUrl === null || $pathOrUrl === '') {
            return;
        }
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return;
        }

        $relative = $pathOrUrl;
        if (str_contains($relative, '/storage/')) {
            $relative = preg_replace('#^.*/storage/#', '', $relative) ?? $relative;
        }

        Storage::disk('public')->delete($relative);
    }
}
