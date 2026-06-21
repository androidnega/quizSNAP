<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class IntelligenceExecutiveExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected array $summary) {}

    public function title(): string
    {
        return 'Executive Summary';
    }

    public function headings(): array
    {
        return ['Section', 'Metric', 'Value'];
    }

    public function collection(): Collection
    {
        $rows = collect();
        $dashboard = $this->summary['executive_dashboard'] ?? [];

        foreach ($dashboard as $key => $value) {
            if (is_scalar($value)) {
                $rows->push(['Executive Dashboard', $key, $value]);
            }
        }

        foreach ($this->summary['students']['at_risk_students'] ?? [] as $student) {
            $rows->push([
                'At-Risk Student',
                $student['student_index'] ?? '',
                'Risk '.$student['risk_score'].' / Performance '.$student['performance_score'],
            ]);
        }

        foreach ($this->summary['recommendations'] ?? [] as $rec) {
            $rows->push(['Recommendation', $rec->title ?? '', $rec->message ?? '']);
        }

        return $rows;
    }
}
