@php $isSuperAdmin = $isSuperAdmin ?? false; @endphp
@extends('layouts.dashboard')

@section('title', 'Student indices — ' . $classGroup->display_name)
@section('dashboard_heading', 'Students')

@section('dashboard_content')
<div class="w-full space-y-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('dashboard.class-groups.show', $classGroup) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ $classGroup->display_name }}
        </a>
        <p class="text-xs text-gray-400 tabular-nums">{{ $students->total() }} indices</p>
    </div>

    @can('update', $classGroup)
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-3 sm:p-4 space-y-2.5">
        <form action="{{ route('dashboard.class-groups.students.add', $classGroup) }}" method="post" class="flex flex-col sm:flex-row sm:items-end gap-2">
            @csrf
            <div class="min-w-0 flex-1">
                <label for="index_number" class="sr-only">Index number</label>
                <input type="text" name="index_number" id="index_number" required maxlength="64" placeholder="Index number e.g. BC/ITS/24/047" value="{{ old('index_number') }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-gray-400 focus:bg-white focus:outline-none focus:ring-0">
            </div>
            <div class="min-w-0 sm:w-48">
                <label for="student_name" class="sr-only">Name</label>
                <input type="text" name="student_name" id="student_name" maxlength="255" placeholder="Name (optional)" value="{{ old('student_name') }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-gray-400 focus:bg-white focus:outline-none focus:ring-0">
            </div>
            <button type="submit" class="inline-flex items-center justify-center rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black shrink-0">Add</button>
        </form>

        <form action="{{ route('dashboard.class-groups.students.upload', $classGroup) }}" method="post" enctype="multipart/form-data" id="students-upload-form" class="flex flex-col sm:flex-row sm:items-center gap-2 border-t border-gray-100 pt-2.5">
            @csrf
            <div class="min-w-0 flex-1">
                <label for="file" class="sr-only">File</label>
                <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" required class="block w-full text-xs text-gray-500 file:mr-2 file:rounded-full file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-gray-700 hover:file:bg-gray-200">
            </div>
            <div class="sm:w-44 shrink-0">
                <label for="upload_mode" class="sr-only">Mode</label>
                <select name="upload_mode" id="upload_mode" required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:border-gray-400 focus:bg-white focus:outline-none focus:ring-0">
                    <option value="merge">Merge with list</option>
                    <option value="replace">Replace list</option>
                </select>
            </div>
            <button type="submit" id="students-upload-btn" class="inline-flex items-center justify-center rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50 shrink-0">Upload</button>
        </form>
        <p class="text-[11px] text-gray-400 leading-snug">Excel/CSV · up to 1,200 rows · runs in background</p>
    </div>
    @else
    <p class="text-sm text-gray-500">View-only. Only coordinators and super admins can add or edit indices.</p>
    @endcan

    @can('update', $classGroup)
    @php
        $activeUploadId = request()->query('upload_id');
        $uploadStatusUrl = $activeUploadId
            ? route('dashboard.class-groups.students.upload.status', [$classGroup, $activeUploadId])
            : null;
        $duplicateResolveUrl = $activeUploadId
            ? route('dashboard.class-groups.students.upload.duplicates', [$classGroup, $activeUploadId])
            : null;
    @endphp

    <div id="student-upload-overlay" class="{{ $activeUploadId ? '' : 'hidden' }} fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
        <div class="w-full max-w-lg rounded-xl bg-white shadow-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900" id="student-upload-title">Processing upload</h3>
                <p class="text-sm text-gray-600 mt-1" id="student-upload-message">Starting…</p>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <span id="student-upload-stats"></span>
                    <span id="student-upload-percent" class="font-semibold text-gray-900">0%</span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div id="student-upload-bar" class="h-full rounded-full bg-primary-600 transition-all duration-300 ease-out" style="width: 0%"></div>
                </div>
                <p id="student-upload-error" class="hidden text-sm text-red-600"></p>
            </div>
            <div id="student-upload-actions" class="hidden px-5 py-4 border-t border-gray-100 bg-gray-50 flex flex-wrap gap-2 justify-end">
                <button type="button" id="student-upload-dismiss" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
            </div>
        </div>
    </div>

    <div id="student-upload-duplicates" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/60 p-4">
        <div class="w-full max-w-2xl max-h-[90vh] flex flex-col rounded-xl bg-white shadow-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 shrink-0">
                <h3 class="text-base font-semibold text-gray-900">Duplicate index numbers found</h3>
                <p class="text-sm text-gray-600 mt-1">These indices already exist. Choose overwrite or skip.</p>
            </div>
            <div class="px-5 py-3 overflow-y-auto flex-1 min-h-0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide border-b border-gray-100">
                            <th class="py-2 pr-3">Index</th>
                            <th class="py-2 pr-3">Uploaded name</th>
                            <th class="py-2">Current name</th>
                        </tr>
                    </thead>
                    <tbody id="student-upload-duplicates-body" class="divide-y divide-gray-100"></tbody>
                </table>
                <p id="student-upload-duplicates-more" class="hidden text-xs text-gray-500 mt-2"></p>
            </div>
            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 shrink-0 flex flex-wrap gap-2 justify-end">
                <form method="post" action="{{ $duplicateResolveUrl ?? '#' }}" id="student-upload-skip-form" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="skip_all">
                    <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Skip all</button>
                </form>
                <form method="post" action="{{ $duplicateResolveUrl ?? '#' }}" id="student-upload-overwrite-form" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="overwrite_all">
                    <button type="submit" class="rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Overwrite all</button>
                </form>
            </div>
        </div>
    </div>
    @endcan

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden flex flex-col max-h-[min(36rem,65vh)]">
        <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2 shrink-0">
            <h2 class="text-sm font-semibold text-gray-900 tracking-tight">Indices</h2>
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('dashboard.class-groups.students.export.excel', $classGroup) }}" class="inline-flex items-center rounded-full border border-gray-200 px-2.5 py-1 text-[11px] font-medium text-gray-600 hover:bg-gray-50" download>Excel</a>
                <a href="{{ route('dashboard.class-groups.students.export.pdf', $classGroup) }}" class="inline-flex items-center rounded-full border border-gray-200 px-2.5 py-1 text-[11px] font-medium text-gray-600 hover:bg-gray-50" download>PDF</a>
                <form method="get" action="{{ route('dashboard.class-groups.students.index', $classGroup) }}" id="student-search-form" class="flex items-center">
                    <label for="student-search" class="sr-only">Search</label>
                    <input type="search" name="search" id="student-search" value="{{ old('search', $search ?? '') }}" placeholder="Search…" class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs text-gray-800 w-36 sm:w-44 focus:border-gray-400 focus:bg-white focus:outline-none" autocomplete="off">
                </form>
                @can('update', $classGroup)
                <form id="bulk-delete-form" action="{{ route('dashboard.class-groups.students.bulk-destroy', $classGroup) }}" method="post" onsubmit="return confirm('Delete all selected students? This also removes their quiz data.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="bulk-delete-btn" class="inline-flex items-center rounded-full bg-rose-50 border border-rose-100 px-2.5 py-1 text-[11px] font-medium text-rose-700 hover:bg-rose-100 disabled:opacity-40 disabled:cursor-not-allowed" disabled>
                        Delete selected
                    </button>
                </form>
                @endcan
            </div>
        </div>
        <div class="dashboard-list-scroll flex-1 min-h-0 overflow-x-auto">
            <table class="w-full divide-y divide-gray-100 min-w-[480px]">
                <thead class="bg-white sticky top-0 z-10">
                    <tr class="border-b border-gray-100">
                        @can('update', $classGroup)
                        <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wide w-10">
                            <input type="checkbox" id="select-all-students" class="h-3.5 w-3.5 text-gray-900 border-gray-300 rounded">
                        </th>
                        @endcan
                        <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wide">Index</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wide">Name</th>
                        <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-400 uppercase tracking-wide">Phone</th>
                        <th class="px-3 py-2 text-right text-[10px] font-medium text-gray-400 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody id="students-tbody" class="divide-y divide-gray-50 bg-white">
                    @forelse($students as $s)
                        @include('admin.class-groups.partials.students-rows', ['students' => collect([$s]), 'classGroup' => $classGroup, 'isSuperAdmin' => $isSuperAdmin])
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-400 text-sm">No students yet.@can('update', $classGroup) Add an index or upload a file.@endcan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
        <div class="border-t border-gray-100 bg-gray-50/80 px-4 py-3 flex flex-wrap items-center justify-between gap-2 shrink-0">
            <p class="text-xs text-gray-500">
                {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }}
            </p>
            <div class="flex flex-wrap justify-end">
                {{ $students->withQueryString()->links() }}
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
    (function() {
        var searchInput = document.getElementById('student-search');
        var searchForm = document.getElementById('student-search-form');
        if (searchInput && searchForm) {
            var debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() { searchForm.submit(); }, 350);
            });
        }

        window.attachStudentCheckboxListeners = function() {
            var bulkBtn = document.getElementById('bulk-delete-btn');
            if (!bulkBtn) return;

            var master = document.getElementById('select-all-students');
            var checkboxes = document.querySelectorAll('.student-select-checkbox');

            function updateBulkState() {
                var anyChecked = false;
                var allChecked = true;
                checkboxes.forEach(function(cb) {
                    if (cb.checked) {
                        anyChecked = true;
                    } else {
                        allChecked = false;
                    }
                });
                bulkBtn.disabled = !anyChecked;
                if (master) {
                    master.checked = allChecked && checkboxes.length > 0;
                    master.indeterminate = anyChecked && !allChecked;
                }
            }

            checkboxes.forEach(function(cb) {
                cb.removeEventListener('change', updateBulkState);
                cb.addEventListener('change', updateBulkState);
            });

            if (master) {
                master.onclick = function() {
                    var checked = master.checked;
                    checkboxes.forEach(function(cb) {
                        cb.checked = checked;
                    });
                    updateBulkState();
                };
            }

            updateBulkState();
        };

        attachStudentCheckboxListeners();

        @can('update', $classGroup)
        @if($uploadStatusUrl)
        (function() {
            var statusUrl = @json($uploadStatusUrl);
            var overlay = document.getElementById('student-upload-overlay');
            var dupModal = document.getElementById('student-upload-duplicates');
            var titleEl = document.getElementById('student-upload-title');
            var messageEl = document.getElementById('student-upload-message');
            var statsEl = document.getElementById('student-upload-stats');
            var percentEl = document.getElementById('student-upload-percent');
            var barEl = document.getElementById('student-upload-bar');
            var errorEl = document.getElementById('student-upload-error');
            var actionsEl = document.getElementById('student-upload-actions');
            var dismissBtn = document.getElementById('student-upload-dismiss');
            var dupBody = document.getElementById('student-upload-duplicates-body');
            var dupMore = document.getElementById('student-upload-duplicates-more');
            var pollTimer = null;
            var stalledCount = 0;

            function escapeHtml(str) {
                if (!str) return '—';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function showOverlay() {
                if (overlay) overlay.classList.remove('hidden');
            }

            function hideOverlay() {
                if (overlay) overlay.classList.add('hidden');
            }

            function showDuplicates(list) {
                if (!dupModal || !dupBody) return;
                var maxShow = 50;
                dupBody.innerHTML = '';
                list.slice(0, maxShow).forEach(function(row) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td class="py-2 pr-3 font-medium text-gray-900">' + escapeHtml(row.index) + '</td>'
                        + '<td class="py-2 pr-3 text-gray-700">' + escapeHtml(row.upload_name) + '</td>'
                        + '<td class="py-2 text-gray-700">' + escapeHtml(row.existing_name) + '</td>';
                    dupBody.appendChild(tr);
                });
                if (list.length > maxShow && dupMore) {
                    dupMore.textContent = 'And ' + (list.length - maxShow) + ' more duplicate(s) not shown.';
                    dupMore.classList.remove('hidden');
                } else if (dupMore) {
                    dupMore.classList.add('hidden');
                }
                dupModal.classList.remove('hidden');
            }

            function hideDuplicates() {
                if (dupModal) dupModal.classList.add('hidden');
            }

            function updateUi(data) {
                var progress = typeof data.progress === 'number' ? data.progress : 0;
                var status = data.status || '';
                var message = data.message || '';

                if (percentEl) percentEl.textContent = progress + '%';
                if (barEl) barEl.style.width = progress + '%';
                if (messageEl) messageEl.textContent = message;

                var processed = data.processed || 0;
                var total = data.total || 0;
                if (statsEl && total > 0) {
                    statsEl.textContent = processed + ' / ' + total + ' rows';
                } else if (statsEl) {
                    statsEl.textContent = '';
                }

                if (errorEl) {
                    if (data.error) {
                        errorEl.textContent = data.error;
                        errorEl.classList.remove('hidden');
                    } else {
                        errorEl.classList.add('hidden');
                    }
                }

                if (status === 'awaiting_duplicate_resolution') {
                    if (titleEl) titleEl.textContent = 'Review duplicates';
                    showDuplicates(data.duplicates || []);
                    hideOverlay();
                    return;
                }

                hideDuplicates();
                showOverlay();

                if (status === 'completed') {
                    if (titleEl) titleEl.textContent = 'Upload complete';
                    if (actionsEl) actionsEl.classList.remove('hidden');
                    if (pollTimer) clearInterval(pollTimer);
                    setTimeout(function() {
                        var url = new URL(window.location.href);
                        url.searchParams.delete('upload_id');
                        window.location.href = url.toString();
                    }, 1500);
                    return;
                }

                if (status === 'failed') {
                    if (titleEl) titleEl.textContent = 'Upload failed';
                    if (actionsEl) actionsEl.classList.remove('hidden');
                    if (pollTimer) clearInterval(pollTimer);
                    return;
                }

                if (titleEl) {
                    titleEl.textContent = status === 'queued' ? 'Queued for processing' : 'Processing upload';
                }
                if (actionsEl) actionsEl.classList.add('hidden');

                if (status === 'queued') {
                    stalledCount++;
                    if (stalledCount >= 8 && messageEl) {
                        messageEl.textContent = message + ' If this stays queued, ensure a queue worker is running (php artisan queue:work).';
                    }
                } else {
                    stalledCount = 0;
                }
            }

            function poll() {
                fetch(statusUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function(res) {
                        if (!res.ok) throw new Error('Upload session not found.');
                        return res.json();
                    })
                    .then(updateUi)
                    .catch(function(err) {
                        if (errorEl) {
                            errorEl.textContent = err.message || 'Could not load upload status.';
                            errorEl.classList.remove('hidden');
                        }
                        if (titleEl) titleEl.textContent = 'Upload status unavailable';
                        if (actionsEl) actionsEl.classList.remove('hidden');
                        if (pollTimer) clearInterval(pollTimer);
                    });
            }

            if (dismissBtn) {
                dismissBtn.addEventListener('click', function() {
                    hideOverlay();
                    var url = new URL(window.location.href);
                    url.searchParams.delete('upload_id');
                    window.history.replaceState({}, '', url.toString());
                });
            }

            ['student-upload-skip-form', 'student-upload-overwrite-form'].forEach(function(id) {
                var form = document.getElementById(id);
                if (!form) return;
                form.addEventListener('submit', function() {
                    hideDuplicates();
                    showOverlay();
                    if (titleEl) titleEl.textContent = 'Processing your choice…';
                    if (pollTimer) clearInterval(pollTimer);
                    pollTimer = setInterval(poll, 1200);
                });
            });

            showOverlay();
            poll();
            pollTimer = setInterval(poll, 1200);
        })();
        @endif
        @endcan
    })();
    </script>
    @endpush
</div>
@endsection
