<?php

namespace App\Jobs;

use App\Models\ClassGroup;
use App\Services\ClassGroupStudentImportService;
use App\Services\ClassGroupStudentUploadProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\AttendanceUploadLog;

class ResolveClassGroupStudentUploadDuplicatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public string $uploadId,
        public string $action
    ) {
        $this->onConnection(ClassGroupStudentUploadProgress::queueConnection());
    }

    public function handle(ClassGroupStudentImportService $importService): void
    {
        $state = ClassGroupStudentUploadProgress::get($this->uploadId);
        if (! $state) {
            return;
        }

        $status = $state['status'] ?? '';
        if (in_array($status, ['completed', 'failed'], true)) {
            return;
        }

        if (! in_array($status, ['awaiting_duplicate_resolution', 'processing'], true)) {
            return;
        }

        $duplicates = $state['duplicates'] ?? [];
        if ($duplicates === [] && $status === 'awaiting_duplicate_resolution') {
            ClassGroupStudentUploadProgress::merge($this->uploadId, [
                'status' => 'failed',
                'error' => 'No duplicates to resolve.',
            ]);

            return;
        }
        $rowsAdded = (int) ($state['rows_added'] ?? 0);
        $rowsUpdated = (int) ($state['rows_updated'] ?? 0);
        $rowsDeleted = (int) ($state['rows_deleted'] ?? 0);
        $rowsSkipped = (int) ($state['rows_skipped_duplicate'] ?? 0);

        $manifestPath = 'student-uploads/' . $this->uploadId . '.json';
        $manifest = Storage::disk('local')->exists($manifestPath)
            ? json_decode(Storage::disk('local')->get($manifestPath), true)
            : null;

        $classGroup = ClassGroup::find($state['class_group_id'] ?? 0);
        if (! $classGroup) {
            ClassGroupStudentUploadProgress::merge($this->uploadId, [
                'status' => 'failed',
                'error' => 'Class group not found.',
            ]);

            return;
        }

        ClassGroupStudentUploadProgress::merge($this->uploadId, [
            'status' => 'processing',
            'message' => $this->action === 'overwrite_all' ? 'Applying duplicate overwrites…' : 'Finalizing upload…',
            'progress' => 0,
        ]);

        $classGroup->load(['level', 'academicYear']);

        if ($this->action === 'overwrite_all') {
            $total = count($duplicates);
            $processed = 0;
            foreach ($duplicates as $dup) {
                $index = trim((string) ($dup['index'] ?? ''));
                if ($index === '') {
                    continue;
                }
                $name = isset($dup['upload_name']) ? trim((string) $dup['upload_name']) : null;
                $name = $name !== '' ? $name : null;

                $importService->importRow($classGroup, $index, $name, true);
                $rowsUpdated++;
                $rowsSkipped = max(0, $rowsSkipped - 1);
                $processed++;
                $progress = $total > 0 ? (int) floor(($processed / $total) * 100) : 100;
                ClassGroupStudentUploadProgress::merge($this->uploadId, [
                    'progress' => min(99, $progress),
                    'message' => 'Overwriting duplicates…',
                ]);
            }
        }

        $mode = $state['mode'] ?? ($manifest['mode'] ?? 'merge');
        $uploadedBy = $state['uploaded_by'] ?? ($manifest['uploaded_by'] ?? null);

        AttendanceUploadLog::create([
            'class_group_id' => $classGroup->id,
            'uploaded_by' => $uploadedBy,
            'upload_mode' => $mode,
            'rows_added' => $rowsAdded,
            'rows_updated' => $rowsUpdated,
            'rows_deleted' => $rowsDeleted,
            'uploaded_at' => now(),
        ]);

        if (Storage::disk('local')->exists($manifestPath)) {
            Storage::disk('local')->delete($manifestPath);
        }

        ClassGroupStudentUploadProgress::merge($this->uploadId, [
            'status' => 'completed',
            'progress' => 100,
            'rows_skipped_duplicate' => $this->action === 'skip_all' ? $rowsSkipped : 0,
            'duplicates' => [],
            'message' => $this->action === 'overwrite_all'
                ? "Upload complete: {$rowsUpdated} duplicate(s) overwritten."
                : "Upload complete: {$rowsSkipped} duplicate(s) skipped.",
            'finished_at' => now()->toIso8601String(),
        ]);

        SendClassGroupStudentLoginOtpsJob::dispatch($classGroup->id)
            ->onConnection(ClassGroupStudentUploadProgress::queueConnection());
    }
}
