<?php

namespace App\Jobs;

use App\Models\AttendanceUploadLog;
use App\Models\ClassGroup;
use App\Services\ClassGroupStudentImportService;
use App\Services\ClassGroupStudentUploadProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\ClassGroupStudent;

class ProcessClassGroupStudentUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 2;

    public function __construct(
        public string $uploadId
    ) {
        $this->onConnection(ClassGroupStudentUploadProgress::queueConnection());
    }

    public function handle(ClassGroupStudentImportService $importService): void
    {
        $manifestPath = 'student-uploads/' . $this->uploadId . '.json';
        if (! Storage::disk('local')->exists($manifestPath)) {
            ClassGroupStudentUploadProgress::merge($this->uploadId, [
                'status' => 'failed',
                'error' => 'Upload session expired or not found.',
                'progress' => 0,
            ]);

            return;
        }

        $manifest = json_decode(Storage::disk('local')->get($manifestPath), true);
        if (! is_array($manifest)) {
            ClassGroupStudentUploadProgress::merge($this->uploadId, [
                'status' => 'failed',
                'error' => 'Invalid upload session data.',
            ]);

            return;
        }

        $classGroup = ClassGroup::find($manifest['class_group_id'] ?? 0);
        if (! $classGroup) {
            ClassGroupStudentUploadProgress::merge($this->uploadId, [
                'status' => 'failed',
                'error' => 'Class group not found.',
            ]);

            return;
        }

        $rows = $manifest['rows'] ?? [];
        $mode = $manifest['mode'] ?? 'merge';
        $total = count($rows);

        ClassGroupStudentUploadProgress::merge($this->uploadId, [
            'status' => 'processing',
            'message' => 'Processing upload…',
            'progress' => 0,
            'total' => $total,
        ]);

        $rowsAdded = 0;
        $rowsUpdated = 0;
        $rowsDeleted = 0;
        $rowsSkippedDuplicate = 0;
        $duplicates = [];
        $processed = 0;

        try {
            if ($mode === 'replace') {
                ClassGroupStudentUploadProgress::merge($this->uploadId, [
                    'message' => 'Removing existing indices…',
                    'progress' => 2,
                ]);
                $rowsDeleted = $importService->clearClassGroupStudents($classGroup);
            }

            $classGroup->load(['level', 'academicYear']);

            foreach ($rows as $row) {
                $index = trim((string) ($row['index'] ?? ''));
                if ($index === '') {
                    $processed++;
                    continue;
                }
                $name = isset($row['name']) ? trim((string) $row['name']) : null;
                $name = $name !== '' ? $name : null;

                if ($mode === 'merge') {
                    $existing = ClassGroupStudent::where('class_group_id', $classGroup->id)
                        ->where('index_number', $index)
                        ->first();
                    if ($existing) {
                        $duplicates[] = [
                            'index' => $index,
                            'upload_name' => $name,
                            'existing_name' => $existing->student_name,
                        ];
                        $rowsSkippedDuplicate++;
                        $processed++;
                        $this->tickProgress($processed, $total, $rowsAdded, $rowsUpdated, $rowsDeleted, $rowsSkippedDuplicate, 'Checking duplicates…');

                        continue;
                    }
                }

                $result = $importService->importRow($classGroup, $index, $name, false);
                if ($result === 'added') {
                    $rowsAdded++;
                } else {
                    $rowsUpdated++;
                }

                $processed++;
                $this->tickProgress($processed, $total, $rowsAdded, $rowsUpdated, $rowsDeleted, $rowsSkippedDuplicate, 'Importing indices…');
            }

            if ($mode === 'merge' && count($duplicates) > 0) {
                ClassGroupStudentUploadProgress::merge($this->uploadId, [
                    'status' => 'awaiting_duplicate_resolution',
                    'progress' => 100,
                    'processed' => $processed,
                    'rows_added' => $rowsAdded,
                    'rows_updated' => $rowsUpdated,
                    'rows_deleted' => $rowsDeleted,
                    'rows_skipped_duplicate' => $rowsSkippedDuplicate,
                    'duplicates' => $duplicates,
                    'message' => count($duplicates) . ' duplicate index(es) need your decision.',
                ]);

                return;
            }

            $this->finalizeUpload($classGroup, $mode, $rowsAdded, $rowsUpdated, $rowsDeleted, $manifest['uploaded_by'] ?? null);
            Storage::disk('local')->delete($manifestPath);

            ClassGroupStudentUploadProgress::merge($this->uploadId, [
                'status' => 'completed',
                'progress' => 100,
                'processed' => $processed,
                'rows_added' => $rowsAdded,
                'rows_updated' => $rowsUpdated,
                'rows_deleted' => $rowsDeleted,
                'rows_skipped_duplicate' => $rowsSkippedDuplicate,
                'duplicates' => [],
                'message' => $this->completionMessage($mode, $rowsAdded, $rowsUpdated, $rowsDeleted, 0),
                'finished_at' => now()->toIso8601String(),
            ]);

            SendClassGroupStudentLoginOtpsJob::dispatch($classGroup->id)
                ->onConnection(ClassGroupStudentUploadProgress::queueConnection());
        } catch (\Throwable $e) {
            report($e);
            ClassGroupStudentUploadProgress::merge($this->uploadId, [
                'status' => 'failed',
                'error' => 'Upload failed: ' . $e->getMessage(),
                'message' => 'Upload failed.',
            ]);
        }
    }

    private function tickProgress(
        int $processed,
        int $total,
        int $rowsAdded,
        int $rowsUpdated,
        int $rowsDeleted,
        int $rowsSkippedDuplicate,
        string $message
    ): void {
        $progress = $total > 0 ? (int) floor(($processed / $total) * 100) : 100;
        ClassGroupStudentUploadProgress::merge($this->uploadId, [
            'progress' => min(99, max(0, $progress)),
            'processed' => $processed,
            'rows_added' => $rowsAdded,
            'rows_updated' => $rowsUpdated,
            'rows_deleted' => $rowsDeleted,
            'rows_skipped_duplicate' => $rowsSkippedDuplicate,
            'message' => $message,
        ]);
    }

    private function finalizeUpload(
        ClassGroup $classGroup,
        string $mode,
        int $rowsAdded,
        int $rowsUpdated,
        int $rowsDeleted,
        ?int $uploadedBy
    ): void {
        AttendanceUploadLog::create([
            'class_group_id' => $classGroup->id,
            'uploaded_by' => $uploadedBy,
            'upload_mode' => $mode,
            'rows_added' => $rowsAdded,
            'rows_updated' => $rowsUpdated,
            'rows_deleted' => $rowsDeleted,
            'uploaded_at' => now(),
        ]);
    }

    private function completionMessage(string $mode, int $added, int $updated, int $deleted, int $skippedDup): string
    {
        if ($mode === 'replace') {
            return 'Replaced list with ' . ($added + $updated) . ' indices' . ($deleted > 0 ? " (removed {$deleted} previous)." : '.');
        }

        $parts = [];
        if ($added > 0) {
            $parts[] = "{$added} added";
        }
        if ($updated > 0) {
            $parts[] = "{$updated} updated";
        }
        if ($skippedDup > 0) {
            $parts[] = "{$skippedDup} duplicates skipped";
        }

        return $parts !== [] ? 'Upload complete: ' . implode(', ', $parts) . '.' : 'Upload complete.';
    }

    public function failed(\Throwable $exception): void
    {
        ClassGroupStudentUploadProgress::merge($this->uploadId, [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'message' => 'Upload failed.',
        ]);
    }
}
