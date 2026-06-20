<?php

namespace App\Http\Controllers;

use App\Support\StoragePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class ClearCacheController extends Controller
{
    /** Use same secret as run-migrations; set MIGRATION_RUN_KEY in .env on live. */
    private const DEFAULT_SECRET = 'QuizSnapMigrate2026Xp9k3m7';

    /**
     * Clear Laravel caches via URL with a secret key.
     * Visit: https://YOUR-LIVE-SITE.com/clear-cache?key=YOUR_SECRET
     */
    public function __invoke(Request $request): Response
    {
        $secret = env('MIGRATION_RUN_KEY', self::DEFAULT_SECRET);
        if ($request->query('key') !== $secret) {
            return response('Invalid or missing key. Use: ?key=YOUR_SECRET (set MIGRATION_RUN_KEY in .env on server).', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $lines = [];
        $lines[] = 'QuizSnap: Clear cache (fix deploy / not showing changes)';
        $lines[] = '========================================================';
        $lines[] = '';

        try {
            $lines[] = 'Removing stale bootstrap cache files...';
            $removed = $this->removeBootstrapCacheFiles();
            $lines[] = $removed > 0
                ? "Removed {$removed} file(s) from bootstrap/cache."
                : 'No bootstrap cache files to remove.';
            $lines[] = '';

            $lines[] = 'Running: optimize:clear';
            Artisan::call('optimize:clear');
            $lines[] = trim(Artisan::output()) ?: 'All caches cleared.';
            $lines[] = '';

            $lines[] = 'Fixing storage permissions...';
            $perm = StoragePermissions::fix(base_path());
            foreach ($perm['lines'] as $line) {
                $lines[] = $line;
            }
            $lines[] = '';
            $lines[] = '========================================================';
            if ($perm['ok']) {
                $lines[] = 'SUCCESS: Caches cleared and storage is writable. Reload your site.';
            } else {
                $lines[] = 'PARTIAL: Caches cleared but storage may still need a chown via SSH.';
                if ($perm['chown_hint']) {
                    $lines[] = $perm['chown_hint'];
                }
            }
        } catch (\Throwable $e) {
            $lines[] = 'Artisan clear failed: '.$e->getMessage();
            $lines[] = 'Attempting manual bootstrap cache cleanup...';
            $removed = $this->removeBootstrapCacheFiles();
            $lines[] = "Removed {$removed} bootstrap cache file(s). Reload the site.";
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    private function removeBootstrapCacheFiles(): int
    {
        $dir = base_path('bootstrap/cache');
        if (! is_dir($dir)) {
            return 0;
        }

        $removed = 0;
        foreach (glob($dir.'/*.php') ?: [] as $file) {
            if (! is_file($file)) {
                continue;
            }
            if (@unlink($file)) {
                $removed++;
            }
        }

        return $removed;
    }
}
