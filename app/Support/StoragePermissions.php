<?php

namespace App\Support;

/**
 * Ensure Laravel writable directories exist and are accessible to the web server user.
 */
final class StoragePermissions
{
    /** @var list<string> */
    public const WRITABLE_DIRS = [
        'storage/app',
        'storage/app/public',
        'storage/app/public/verification',
        'storage/app/public/violations',
        'storage/framework',
        'storage/framework/cache',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache',
    ];

    /**
     * @return array{ok: bool, lines: list<string>, chown_hint: ?string}
     */
    public static function fix(string $basePath): array
    {
        $lines = [];
        $webUser = self::detectWebUser();
        $allOk = true;

        foreach (self::WRITABLE_DIRS as $relative) {
            $path = rtrim($basePath, '/').'/'.$relative;
            if (! is_dir($path)) {
                if (@mkdir($path, 0775, true)) {
                    $lines[] = "Created: {$relative}";
                } else {
                    $lines[] = "Failed to create: {$relative}";
                    $allOk = false;
                    continue;
                }
            }

            if (! @chmod($path, 0775)) {
                $lines[] = "chmod 775 failed: {$relative}";
            }

            self::chmodTree($path, $lines, 500);
        }

        $viewsPath = rtrim($basePath, '/').'/storage/framework/views';
        $testFile = $viewsPath.'/.__write_test_'.uniqid('', true);
        $canWrite = @file_put_contents($testFile, 'ok') !== false;
        if ($canWrite) {
            @unlink($testFile);
            $lines[] = 'Write test OK: storage/framework/views';
        } else {
            $allOk = false;
            $lines[] = 'Write test FAILED: storage/framework/views (web server cannot compile Blade views)';
        }

        $logsPath = rtrim($basePath, '/').'/storage/logs';
        if (! is_file($logsPath.'/laravel.log')) {
            @touch($logsPath.'/laravel.log');
            @chmod($logsPath.'/laravel.log', 0664);
        }
        $logTestFile = $logsPath.'/.__write_test_'.uniqid('', true);
        $canWriteLogs = @file_put_contents($logTestFile, 'ok') !== false;
        if ($canWriteLogs) {
            @unlink($logTestFile);
            $lines[] = 'Write test OK: storage/logs';
        } else {
            $allOk = false;
            $lines[] = 'Write test FAILED: storage/logs (application errors may not be logged to file)';
        }

        $chownHint = null;
        if (! $allOk && $webUser !== null) {
            $chownHint = "sudo chown -R {$webUser}:{$webUser} storage bootstrap/cache && sudo chmod -R ug+rwx storage bootstrap/cache";
            $lines[] = 'Run on server (SSH): '.$chownHint;
        } elseif (! $allOk) {
            $chownHint = 'sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R ug+rwx storage bootstrap/cache';
            $lines[] = 'Run on server (SSH): '.$chownHint;
        }

        return ['ok' => $allOk, 'lines' => $lines, 'chown_hint' => $chownHint];
    }

    /**
     * @param  list<string>  $lines
     */
    private static function chmodTree(string $dir, array &$lines, int $maxItems): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $count = 0;
        foreach ($iterator as $item) {
            if ($count >= $maxItems) {
                $lines[] = '  (truncated chmod pass under '.basename($dir).')';
                break;
            }
            $pathname = $item->getPathname();
            if ($item->isDir()) {
                @chmod($pathname, 0775);
            } else {
                @chmod($pathname, 0664);
            }
            $count++;
        }
    }

    public static function detectWebUser(): ?string
    {
        $fromEnv = env('WEB_SERVER_USER');
        if (is_string($fromEnv) && trim($fromEnv) !== '') {
            return trim($fromEnv);
        }

        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $info = posix_getpwuid(posix_geteuid());
            if (is_array($info) && ! empty($info['name'])) {
                return (string) $info['name'];
            }
        }

        return 'www-data';
    }
}
