<?php

namespace App\Services;

use App\Models\SupportMessage;
use App\Models\SupportSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportChatMediaService
{
    private const DISK = 'public';

    private const BASE_DIR = 'support-chat';

    public function sessionDir(SupportSession $session): string
    {
        return self::BASE_DIR.'/'.$session->uuid;
    }

    /** @return array{path: string, url: string} */
    public function storeImage(SupportSession $session, UploadedFile $file): array
    {
        $this->ensureSessionDir($session);
        $path = $file->store($this->sessionDir($session), self::DISK);

        return $this->payloadForPath($session, $path);
    }

    /** @return array{path: string, url: string, mime?: string} */
    public function storeAudio(SupportSession $session, UploadedFile $file): array
    {
        $this->ensureSessionDir($session);
        $path = $file->store($this->sessionDir($session), self::DISK);
        $payload = $this->payloadForPath($session, $path);
        $payload['mime'] = $file->getMimeType() ?: 'audio/webm';

        return $payload;
    }

    public function streamFile(SupportSession $session, string $filename): StreamedResponse
    {
        $filename = basename($filename);
        if ($filename === '' || str_contains($filename, '..')) {
            abort(404);
        }

        $path = $this->sessionDir($session).'/'.$filename;
        if (! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        $mime = Storage::disk(self::DISK)->mimeType($path) ?: 'application/octet-stream';

        return Storage::disk(self::DISK)->response($path, $filename, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function ensureSessionDir(SupportSession $session): void
    {
        $dir = $this->sessionDir($session);
        if (! Storage::disk(self::DISK)->exists($dir)) {
            Storage::disk(self::DISK)->makeDirectory($dir);
        }
    }

    /** @return array{path: string, url: string} */
    private function payloadForPath(SupportSession $session, string $path): array
    {
        return [
            'path' => $path,
            'url' => route('support.sessions.media', [
                'uuid' => $session->uuid,
                'filename' => basename($path),
            ]),
        ];
    }

    public function purgeSession(SupportSession $session): void
    {
        $session->loadMissing('messages');

        foreach ($session->messages as $message) {
            $this->deleteMessageFile($message);
        }

        Storage::disk(self::DISK)->deleteDirectory($this->sessionDir($session));
    }

    public function deleteMessageFile(SupportMessage $message): void
    {
        $meta = $message->meta;
        if (! is_array($meta)) {
            return;
        }

        $path = $meta['path'] ?? null;
        if (! is_string($path) || $path === '') {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
