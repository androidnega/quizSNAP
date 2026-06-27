@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4 sm:space-y-6">
    <div class="min-w-0">
        <p class="text-sm sm:text-base text-gray-600">Institutions, users, students, class groups, and system settings</p>
    </div>

    {{-- Update mode: very slim height, clean, no animation; countdown mm:ss, no overflow --}}
    <section class="rounded-lg border px-2.5 py-1.5 min-w-0 overflow-hidden {{ ($update_mode ?? false) ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0 flex-1 flex items-center gap-2 flex-wrap">
                <h2 class="text-xs font-semibold {{ ($update_mode ?? false) ? 'text-green-900' : 'text-gray-900' }}">Update mode</h2>
                <span class="text-xs font-medium px-1.5 py-0.5 rounded {{ ($update_mode ?? false) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">{{ ($update_mode ?? false) ? 'ON' : 'OFF' }}</span>
                @if(($update_mode ?? false) && ($update_estimated_end ?? null))
                    <span class="text-xs text-green-900 font-semibold tabular-nums shrink-0 overflow-hidden" style="max-width:100%">Time left: <span id="update-mode-countdown">--:--</span></span>
                @endif
                <span class="text-xs {{ ($update_mode ?? false) ? 'text-green-800' : 'text-gray-600' }}">Only staff at <code class="px-0.5 rounded {{ ($update_mode ?? false) ? 'bg-green-100' : 'bg-gray-200' }}">/login</code></span>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                @if($update_mode ?? false)
                    <form method="post" action="{{ route('dashboard.settings.update-estimated-end') }}" class="flex items-center gap-1.5">
                        @csrf
                        <label class="sr-only">Estimated end</label>
                        <input type="datetime-local" name="estimated_end" value="{{ $update_estimated_end ? \Carbon\Carbon::parse($update_estimated_end)->format('Y-m-d\TH:i') : '' }}" class="text-xs rounded border border-green-300 px-1.5 py-0.5 min-w-0 w-36" />
                        <button type="submit" class="text-xs font-medium text-green-800 py-0.5">Save</button>
                    </form>
                @endif
                <form method="post" action="{{ route('dashboard.settings.update-mode') }}" class="inline">
                    @csrf
                    <button type="submit" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent focus:outline-none focus:ring-1 focus:ring-offset-0 {{ ($update_mode ?? false) ? 'bg-green-500 focus:ring-green-400' : 'bg-gray-300 focus:ring-gray-400' }}" role="switch" aria-checked="{{ ($update_mode ?? false) ? 'true' : 'false' }}">
                        <span class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow {{ ($update_mode ?? false) ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- Live activity (real-time; polled from server, not page-cached) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 min-w-0">
        <div id="live-visitors-card" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-gray-500">Live on site</p>
                    <p id="live-visitors-count" class="mt-0.5 text-2xl sm:text-3xl font-bold tabular-nums text-gray-900">{{ (int) ($liveVisitors ?? 0) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Visitors active in the last 90 seconds</p>
                </div>
            </div>
        </div>
        <div id="live-quiz-takers-card" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-gray-500">Writing quiz now</p>
                    <p id="live-quiz-takers-count" class="mt-0.5 text-2xl sm:text-3xl font-bold tabular-nums text-gray-900">{{ (int) ($liveQuizTakers ?? 0) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Students with a live quiz heartbeat (90s)</p>
                </div>
            </div>
        </div>
    </div>

    @php($infra = $infrastructure ?? [])
    <section class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4 min-w-0" id="infrastructure-status-panel">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h2 class="text-xs font-semibold text-gray-700">Server & infrastructure</h2>
            <span class="text-[10px] text-gray-500 tabular-nums" id="infra-checked-at">Updated {{ isset($infra['checked_at']) ? \Carbon\Carbon::parse($infra['checked_at'])->diffForHumans() : 'just now' }}</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 min-w-0">
            @foreach([
                ['id' => 'infra-cpu', 'label' => 'CPU cores', 'value' => $infra['cpu_cores'] ?? '—', 'hint' => isset($infra['cpu_usage']) ? ($infra['cpu_usage'].'% used') : null, 'icon' => 'bg-orange-50 text-orange-700'],
                ['id' => 'infra-ram', 'label' => 'RAM used', 'value' => isset($infra['ram_usage']) ? $infra['ram_usage'].'%' : '—', 'hint' => isset($infra['ram_used_mb'], $infra['ram_total_mb']) ? ($infra['ram_used_mb'].' / '.$infra['ram_total_mb'].' MB') : null, 'icon' => 'bg-blue-50 text-blue-700'],
                ['id' => 'infra-disk', 'label' => 'Disk used', 'value' => isset($infra['disk_usage']) ? $infra['disk_usage'].'%' : '—', 'hint' => isset($infra['disk_free_gb']) ? ($infra['disk_free_gb'].' GB free') : null, 'icon' => 'bg-slate-50 text-slate-700'],
                ['id' => 'infra-redis', 'label' => 'Redis', 'value' => ($infra['redis']['status'] ?? 'offline') === 'online' ? 'Live' : 'Offline', 'hint' => $infra['redis']['label'] ?? '—', 'icon' => ($infra['redis']['status'] ?? '') === 'online' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'],
                ['id' => 'infra-db', 'label' => 'Database', 'value' => ($infra['database']['status'] ?? 'offline') === 'online' ? 'Active' : 'Down', 'hint' => $infra['database']['label'] ?? '—', 'icon' => ($infra['database']['status'] ?? '') === 'online' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'],
                ['id' => 'infra-workers', 'label' => 'Workers', 'value' => ($infra['queue_workers'] ?? 0).' queue', 'hint' => ($infra['reverb_workers'] ?? 0).' reverb', 'icon' => 'bg-indigo-50 text-indigo-700'],
            ] as $card)
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 min-w-0" id="{{ $card['id'] }}">
                    <p class="text-[11px] font-medium text-gray-500 truncate">{{ $card['label'] }}</p>
                    <p class="mt-0.5 text-lg font-bold tabular-nums text-gray-900 infra-value">{{ $card['value'] }}</p>
                    @if($card['hint'])<p class="mt-0.5 text-[10px] text-gray-500 truncate infra-hint">{{ $card['hint'] }}</p>@endif
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4 min-w-0">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700" aria-hidden="true">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500 truncate">Staff users</p>
                    <p class="mt-0.5 text-xl sm:text-2xl font-bold tabular-nums text-gray-900">{{ $overview['users'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700" aria-hidden="true">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500 truncate">Courses</p>
                    <p class="mt-0.5 text-xl sm:text-2xl font-bold tabular-nums text-gray-900">{{ $overview['courses'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-700" aria-hidden="true">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500 truncate">Class groups</p>
                    <p class="mt-0.5 text-xl sm:text-2xl font-bold tabular-nums text-gray-900">{{ $overview['class_groups'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm min-w-0">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700" aria-hidden="true">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500 truncate">Students</p>
                    <p class="mt-0.5 text-xl sm:text-2xl font-bold tabular-nums text-gray-900">{{ $overview['students'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4 min-w-0">
        <h2 class="text-xs font-semibold text-gray-700 mb-3">Quick links</h2>
        <div class="flex flex-wrap gap-2 sm:gap-2">
            <a href="{{ route('dashboard.students.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 min-h-[44px] hover:bg-gray-100 hover:border-gray-300 transition-colors touch-manipulation" title="Search and manage all students">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-gray-500"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Students</span>
            </a>
            <a href="{{ route('dashboard.class-groups.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 min-h-[44px] hover:bg-gray-100 hover:border-gray-300 transition-colors touch-manipulation" title="Browse class groups across institutions">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-gray-500"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Class Groups</span>
            </a>
            <a href="{{ route('dashboard.support.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2.5 min-h-[44px] hover:bg-indigo-100 hover:border-indigo-300 transition-colors touch-manipulation" title="Live student support chat">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-indigo-600"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Live Support</span>
            </a>
            <a href="{{ route('dashboard.settings.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 min-h-[44px] hover:bg-gray-100 hover:border-gray-300 transition-colors touch-manipulation" title="Configure app, mail, AI, and storage">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-gray-500"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Settings</span>
            </a>
            <a href="{{ route('dashboard.institutions.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 min-h-[44px] hover:bg-gray-100 hover:border-gray-300 transition-colors touch-manipulation" title="Manage institutions and assign examiners">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-gray-500"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Institutions</span>
            </a>
            <a href="{{ route('dashboard.users.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 min-h-[44px] hover:bg-gray-100 hover:border-gray-300 transition-colors touch-manipulation" title="Manage staff (Super Admin and Examiners)">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-gray-500"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Users</span>
            </a>
            <a href="{{ route('dashboard.system.reset.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-danger-200 bg-danger-50 px-3 py-2.5 min-h-[44px] hover:bg-danger-100 hover:border-danger-300 transition-colors touch-manipulation" title="Clear data or full system reset (use with caution)">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-danger-600"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Reset</span>
            </a>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4 min-w-0">
        <h2 class="text-xs font-semibold text-gray-700 mb-3">System Monitoring</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 min-w-0">
            <a href="{{ route('dashboard.monitoring.overview') }}" class="group rounded-xl border border-gray-200 bg-gray-50 p-4 transition hover:border-primary-300 hover:bg-white hover:shadow-sm min-w-0">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-gray-900 group-hover:text-primary-700">Monitoring Center</h3>
                        <p class="mt-0.5 text-xs text-gray-600">Errors, activity logs, queue, server health, security.</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('dashboard.operations.index') }}" class="group rounded-xl border border-gray-200 bg-gray-50 p-4 transition hover:border-primary-300 hover:bg-white hover:shadow-sm min-w-0">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-gray-900 group-hover:text-primary-700">Operations Center</h3>
                        <p class="mt-0.5 text-xs text-gray-600">Live exams, proctoring, attendance, incidents.</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('dashboard.intelligence.index') }}" class="group rounded-xl border border-gray-200 bg-gray-50 p-4 transition hover:border-primary-300 hover:bg-white hover:shadow-sm min-w-0">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-gray-900 group-hover:text-primary-700">Intelligence Center</h3>
                        <p class="mt-0.5 text-xs text-gray-600">Academic analytics, risk, predictive insights, reports.</p>
                    </div>
                </div>
            </a>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function () {
    var visitorsEl = document.getElementById('live-visitors-count');
    var quizEl = document.getElementById('live-quiz-takers-count');
    if (!visitorsEl && !quizEl) return;

    var liveStatsUrl = @json(route('dashboard.live-stats'));
    var pollMs = 12000;
    var pollTimer = null;

    function applyStats(data) {
        if (!data || !data.success) return;
        if (visitorsEl && typeof data.visitors === 'number') {
            visitorsEl.textContent = String(data.visitors);
        }
        if (quizEl && typeof data.quiz_takers === 'number') {
            quizEl.textContent = String(data.quiz_takers);
        }
        if (data.infrastructure) {
            applyInfrastructure(data.infrastructure);
        }
    }

    function applyInfrastructure(infra) {
        if (!infra) return;
        var map = {
            'infra-cpu': { value: infra.cpu_cores != null ? String(infra.cpu_cores) : '—', hint: infra.cpu_usage != null ? infra.cpu_usage + '% used' : '' },
            'infra-ram': { value: infra.ram_usage != null ? infra.ram_usage + '%' : '—', hint: (infra.ram_used_mb != null && infra.ram_total_mb != null) ? infra.ram_used_mb + ' / ' + infra.ram_total_mb + ' MB' : '' },
            'infra-disk': { value: infra.disk_usage != null ? infra.disk_usage + '%' : '—', hint: infra.disk_free_gb != null ? infra.disk_free_gb + ' GB free' : '' },
            'infra-redis': { value: (infra.redis && infra.redis.status === 'online') ? 'Live' : 'Offline', hint: (infra.redis && infra.redis.label) || '' },
            'infra-db': { value: (infra.database && infra.database.status === 'online') ? 'Active' : 'Down', hint: (infra.database && infra.database.label) || '' },
            'infra-workers': { value: (infra.queue_workers != null ? infra.queue_workers : 0) + ' queue', hint: (infra.reverb_workers != null ? infra.reverb_workers : 0) + ' reverb' },
        };
        Object.keys(map).forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            var valEl = el.querySelector('.infra-value');
            var hintEl = el.querySelector('.infra-hint');
            if (valEl) valEl.textContent = map[id].value;
            if (hintEl) hintEl.textContent = map[id].hint;
        });
        var checked = document.getElementById('infra-checked-at');
        if (checked) checked.textContent = 'Updated just now';
    }

    function fetchLiveStats() {
        fetch(liveStatsUrl, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache',
            },
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(applyStats)
            .catch(function () {});
    }

    function schedulePoll() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(fetchLiveStats, pollMs);
    }

    fetchLiveStats();
    schedulePoll();

    if (window.QuizSnapLive && typeof window.QuizSnapLive.registerRefresher === 'function') {
        window.QuizSnapLive.registerRefresher(function (type) {
            if (window.QuizSnapLive.isUserInteracting && window.QuizSnapLive.isUserInteracting()) {
                return;
            }
            if (type === 'sessions' || type === 'dashboard') {
                fetchLiveStats();
            }
        });
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') fetchLiveStats();
    });
})();
</script>
@endpush

@if(($update_mode ?? false) && ($update_estimated_end ?? null))
@push('scripts')
<script>
(function () {
    var el = document.getElementById('update-mode-countdown');
    if (!el) return;
    var endMs = new Date("{{ \Carbon\Carbon::parse($update_estimated_end)->toIso8601String() }}").getTime();
    if (!endMs || Number.isNaN(endMs)) return;
    function formatLeft(totalSeconds) {
        totalSeconds = Math.max(0, Math.floor(totalSeconds));
        var h = Math.floor(totalSeconds / 3600);
        var m = Math.floor((totalSeconds % 3600) / 60);
        var s = totalSeconds % 60;
        if (h > 0) {
            return String(h) + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }
    function tick() {
        var left = Math.max(0, Math.ceil((endMs - Date.now()) / 1000));
        el.textContent = formatLeft(left);
        if (left <= 0) {
            clearInterval(timer);
        }
    }
    tick();
    var timer = setInterval(tick, 1000);
})();
</script>
@endpush
@endif
@endsection
