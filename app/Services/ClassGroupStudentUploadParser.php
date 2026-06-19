<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClassGroupStudentUploadParser
{
    public const MAX_ROWS = 1200;

    /**
     * @return array{rows: array<int, array{index: string, name: ?string}>, duplicates_in_file: int}
     */
    public static function parse(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        if (empty($rows)) {
            throw ValidationException::withMessages([
                'file' => 'The file is empty.',
            ]);
        }

        $header = array_shift($rows);
        $indexCol = 0;
        $nameCol = 1;
        foreach ($header as $i => $h) {
            $h = is_string($h) ? strtolower($h) : '';
            if (str_contains($h, 'index') || $i === 0) {
                $indexCol = $i;
            }
            if (str_contains($h, 'name') || str_contains($h, 'student')) {
                $nameCol = $i;
            }
        }

        $byIndex = [];
        $rawCount = 0;
        foreach ($rows as $row) {
            $index = trim((string) ($row[$indexCol] ?? ''));
            if ($index === '') {
                continue;
            }
            $rawCount++;
            $name = isset($row[$nameCol]) ? trim((string) $row[$nameCol]) : null;
            $name = $name !== '' ? $name : null;
            $byIndex[$index] = ['index' => $index, 'name' => $name];
        }

        if ($rawCount === 0) {
            throw ValidationException::withMessages([
                'file' => 'No index numbers found in the file.',
            ]);
        }

        if (count($byIndex) > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'file' => 'Maximum ' . self::MAX_ROWS . ' index numbers per upload. Your file has ' . count($byIndex) . '.',
            ]);
        }

        return [
            'rows' => array_values($byIndex),
            'duplicates_in_file' => max(0, $rawCount - count($byIndex)),
        ];
    }
}
