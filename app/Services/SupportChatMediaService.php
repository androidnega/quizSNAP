<?php

namespace App\Services;

use App\Models\SupportMessage;
use App\Models\SupportSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
        $path = $file->store($this->sessionDir($session), self::DISK);

        return $this->payloadForPath($path);
    }

    /** @return array{path: string, url: string, mime?: string} */
    public function storeAudio(SupportSession $session, UploadedFile $file): array
    {
        $path = $file->store($this->sessionDir($session), self::DISK);
        $payload = $this->payloadForPath($path);
        $payload['mime'] = $file->getMimeType() ?: 'audio/webm';

        return $payload;
    }

    /** @return array{path: string, url: string} */
    private function payloadForPath(string $path): array
    {
        $relative = Storage::disk(self::DISK)->url($path);

        return [
            'path' => $path,
            'url' => str_starts_with($relative, 'http')
                ? $relative
                : url($relative),
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
