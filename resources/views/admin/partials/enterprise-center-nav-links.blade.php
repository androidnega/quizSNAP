<li>
    <a href="{{ route('dashboard.monitoring.overview') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.monitoring.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="Enterprise monitoring and observability">
        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span class="examiner-nav-text truncate">Monitoring Center</span>
    </a>
</li>
<li>
    <a href="{{ route('dashboard.operations.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.operations.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="Live exam and academic operations">
        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        <span class="examiner-nav-text truncate">Operations Center</span>
    </a>
</li>
<li>
    <a href="{{ route('dashboard.intelligence.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.intelligence.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all" title="Academic intelligence and predictive analytics">
        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        <span class="examiner-nav-text truncate">Intelligence Center</span>
    </a>
</li>
