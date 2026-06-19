<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProctoringMediaController extends Controller
{
    /**
     * Serve proctoring images from the public disk when the storage symlink is missing.
     */
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->isStaff()) {
            abort(403);
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || str_contains($path, '..') || ! preg_match('#^(verification|violations)/#', $path)) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            abort(404);
        }

        return response()->file($disk->path($path), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
