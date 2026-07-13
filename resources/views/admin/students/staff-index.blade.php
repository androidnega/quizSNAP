@extends('layouts.dashboard')

@section('title', 'Students')
@section('dashboard_heading')
    <span class="inline-flex items-center gap-2"><i class="fas fa-user-graduate text-primary-600"></i> Students</span>
@endsection

@section('dashboard_content')
<div class="w-full space-y-4">
    <p class="text-sm text-gray-600">
        @if($isSuperAdmin)
            Search and manage students across all institutions. Open a student to view full details, edit their index, name, or phone, or manage them within their class group.
        @else
            Search and manage students in your faculty or department. Open a student to view full details or edit their information.
        @endif
    </p>

    <div id="live-search-panel" class="hidden" data-live-search="1" data-param="search" aria-hidden="true"></div>

    <form method="get" action="{{ route('dashboard.students.index') }}" id="staff-students-filter-form" class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4">
        <div class="min-w-[200px] flex-1">
            <label for="student_search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="search" name="search" id="student_search" value="{{ $search }}" placeholder="Index, name, or phone" autocomplete="off" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-primary-400 focus:ring-1 focus:ring-primary-200 focus:outline-none">
        </div>
        <div>
            <label for="filter_class_group" class="block text-xs font-medium text-gray-500 mb-1">Class group</label>
            <select name="class_group_id" id="filter_class_group" class="block w-full min-w-[180px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                <option value="">All groups</option>
                @foreach($classGroups as $group)
                    <option value="{{ $group->id }}" {{ (string) request('class_group_id') === (string) $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>
        @if($isSuperAdmin && $institutions->isNotEmpty())
        <div>
            <label for="filter_institution" class="block text-xs font-medium text-gray-500 mb-1">Institution</label>
            <select name="institution_id" id="filter_institution" class="block w-full min-w-[200px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                <option value="">All institutions</option>
                @foreach($institutions as $inst)
                    <option value="{{ $inst->id }}" {{ (string) request('institution_id') === (string) $inst->id ? 'selected' : '' }}>{{ $inst->display_name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="flex items-end gap-2">
            <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">Search</button>
            <a href="{{ route('dashboard.students.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Clear</a>
        </div>
    </form>

    <p id="live-search-meta" class="text-xs text-gray-400 tabular-nums">{{ $students->total() }} students</p>

    <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Index</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Phone</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Class group</th>
                        @if($isSuperAdmin)
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Institution</th>
                        @endif
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide" title="Times the student password was changed">Pw changes</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody id="live-search-results" class="divide-y divide-gray-100 bg-white">
                    @include('admin.students.partials.staff-rows', ['students' => $students, 'isSuperAdmin' => $isSuperAdmin, 'search' => $search])
                </tbody>
            </table>
        </div>
    </div>

    <div id="live-search-pagination-wrap" class="{{ $students->hasPages() ? '' : 'hidden' }}">
        <div id="live-search-pagination">
            @if($students->hasPages())
                {{ $students->links() }}
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var form = document.getElementById('staff-students-filter-form');
    var searchInput = document.getElementById('student_search');
    var headerInput = document.getElementById('dashboard-global-search');
    var results = document.getElementById('live-search-results');
    var pagination = document.getElementById('live-search-pagination');
    var paginationWrap = document.getElementById('live-search-pagination-wrap');
    var meta = document.getElementById('live-search-meta');
    var abortCtrl = null;
    var debounceTimer = null;
    var lastQuery = String(searchInput && searchInput.value || '');

    function applyPayload(data, url) {
        if (results && typeof data.html === 'string') results.innerHTML = data.html;
        if (pagination) pagination.innerHTML = data.pagination || '';
        if (paginationWrap) paginationWrap.classList.toggle('hidden', !data.pagination);
        if (meta && data.meta) {
            meta.textContent = data.meta;
            meta.classList.remove('hidden');
        }
        if (url) { try { history.replaceState(null, '', url); } catch (e) {} }
    }
    window.QuizsnapLiveSearchApply = applyPayload;

    function currentFilters() {
        var url = new URL(window.location.href);
        if (form) {
            var fd = new FormData(form);
            fd.forEach(function(v, k) {
                if (v) url.searchParams.set(k, v);
                else url.searchParams.delete(k);
            });
        }
        url.searchParams.delete('page');
        return url;
    }

    function fetchLive() {
        var url = currentFilters();
        var fetchUrl = new URL(url.toString());
        fetchUrl.searchParams.set('ajax', '1');
        if (abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();
        results && results.classList.add('opacity-60');
        fetch(fetchUrl.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: abortCtrl.signal,
        })
            .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
            .then(function(payload) {
                if (!payload.ok || !payload.data) return;
                applyPayload(payload.data, url.pathname + url.search + url.hash);
            })
            .catch(function(err) { if (err && err.name === 'AbortError') return; })
            .finally(function() { results && results.classList.remove('opacity-60'); });
    }

    window.QuizsnapLiveSearchRun = function(query) {
        query = String(query || '').trim();
        if (searchInput && document.activeElement !== searchInput) searchInput.value = query;
        if (headerInput && document.activeElement !== headerInput) headerInput.value = query;
        lastQuery = query;
        fetchLive();
    };

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            clearTimeout(debounceTimer);
            window.QuizsnapLiveSearchRun(searchInput ? searchInput.value : '');
        });
        ['filter_class_group', 'filter_institution'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', function() { window.QuizsnapLiveSearchRun(searchInput ? searchInput.value : ''); });
        });
    }
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                var q = String(searchInput.value || '').trim();
                if (q === lastQuery) return;
                window.QuizsnapLiveSearchRun(q);
            }, 320);
        });
    }
})();
</script>
@endpush
