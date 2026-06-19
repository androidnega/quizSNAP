<?php

namespace App\Services;

use App\Models\AuthAuditLog;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentAuthAuditLogger
{
    public static function log(
        string $event,
        ?Student $student = null,
        ?string $indexHash = null,
        ?Request $request = null,
        array $meta = []
    ): void {
        try {
            $req = $request ?? request();
            AuthAuditLog::create([
                'actor_type' => 'student',
                'actor_id' => $student?->id,
                'index_number_hash' => $indexHash ?? $student?->index_number_hash,
                'event' => $event,
                'ip_address' => $req?->ip(),
                'user_agent' => $req?->userAgent() ? substr((string) $req->userAgent(), 0, 500) : null,
                'meta' => $meta !== [] ? $meta : null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
