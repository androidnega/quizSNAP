<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Intelligence Executive Report</title><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#111}h1{font-size:18px}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f5f5f5}</style></head><body>
<h1>QuizSnap Intelligence Executive Report</h1>
<p>Generated: {{ $summary['generated_at'] ?? now()->toIso8601String() }}</p>
<h2>Health Scores</h2>
<table><tbody>
@foreach(['institution_health_score','academic_health_score','student_success_score','attendance_score','integrity_score','risk_score','risk_level'] as $key)
<tr><td>{{ str_replace('_',' ', $key) }}</td><td>{{ $dashboard[$key] ?? '—' }}</td></tr>
@endforeach
</tbody></table>
<h2>At-Risk Students</h2>
<table><thead><tr><th>Student</th><th>Risk</th><th>Performance</th></tr></thead><tbody>
@foreach(($summary['students']['at_risk_students'] ?? []) as $student)
<tr><td>{{ $student['student_index'] ?? '' }}</td><td>{{ $student['risk_score'] ?? '' }}</td><td>{{ $student['performance_score'] ?? '' }}</td></tr>
@endforeach
</tbody></table>
</body></html>
