<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Operations Wallboard</title>
    @include('partials.theme-styles')
    <script>window.OPERATIONS_ACCESS = true;</script>
    <script src="{{ asset('js/quizsnap-reverb.js') }}?v={{ filemtime(public_path('js/quizsnap-reverb.js')) }}"></script>
    <script src="{{ asset('js/quizsnap-operations.js') }}" defer></script>
</head>
<body class="bg-slate-900 text-white min-h-screen p-6" data-operations-page="wallboard">
    <div id="operations-wallboard-root">
        <header class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">QuizSnap Operations Wallboard</h1>
                <p class="text-slate-400 text-sm">Live exam and campus operations</p>
            </div>
            <span id="operations-live-indicator" class="rounded-full bg-emerald-500/20 px-3 py-1 text-emerald-300 text-sm">Live</span>
        </header>
        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4 mb-6">
            @foreach([
                'active_exams' => 'Active Exams',
                'students_writing' => 'Writing',
                'students_disconnected' => 'Disconnected',
                'suspicious_activities' => 'Proctoring Alerts',
                'open_incidents' => 'Incidents',
                'users_online' => 'Users Online',
            ] as $key => $label)
                <div class="rounded-2xl bg-slate-800 p-4 border border-slate-700">
                    <p class="text-xs text-slate-400">{{ $label }}</p>
                    <p class="text-3xl font-bold mt-1" data-operations-stat="{{ $key }}">{{ number_format($payload[$key] ?? 0) }}</p>
                </div>
            @endforeach
        </div>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <div class="rounded-2xl bg-slate-800 p-4 border border-slate-700">
                <h2 class="text-sm font-semibold text-slate-300 mb-3">System Health</h2>
                @if($payload['system_health'] ?? null)
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-slate-400">Status</dt><dd>{{ $payload['system_health']['status'] ?? '—' }}</dd></div>
                        <div><dt class="text-slate-400">CPU</dt><dd>{{ $payload['system_health']['cpu'] ?? '—' }}%</dd></div>
                        <div><dt class="text-slate-400">RAM</dt><dd>{{ $payload['system_health']['ram'] ?? '—' }}%</dd></div>
                        <div><dt class="text-slate-400">Disk</dt><dd>{{ $payload['system_health']['disk'] ?? '—' }}%</dd></div>
                    </dl>
                @else
                    <p class="text-slate-500 text-sm">No health data.</p>
                @endif
            </div>
            <div class="rounded-2xl bg-slate-800 p-4 border border-slate-700">
                <h2 class="text-sm font-semibold text-slate-300 mb-3">Live Feed</h2>
                <div id="operations-activity-feed" class="space-y-2 max-h-64 overflow-y-auto text-sm">
                    @forelse($payload['feed'] ?? [] as $item)
                        <div class="rounded-lg bg-slate-900/60 px-3 py-2">{{ $item['student'] ?? $item['exam'] ?? 'Activity' }} — {{ $item['status'] ?? ($item['label'] ?? '') }}</div>
                    @empty
                        <p class="text-slate-500">Waiting for activity…</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</body>
</html>
