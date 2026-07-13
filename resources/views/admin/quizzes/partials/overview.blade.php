@php
    $approvedCount = $quiz->questions()->count();
    $neededCount = $quiz->getQuestionsPerStudent();
    $shortBy = max(0, $neededCount - $approvedCount);
@endphp

{{-- Questions summary --}}
<section class="mb-4 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="px-4 py-4 sm:px-5">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-gray-900 tracking-tight">Questions</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $approvedQuestionsTotal ?? 0 }} approved · {{ $unapprovedPoolsTotal ?? 0 }} in pool</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 ml-auto">
                @if(($approvedQuestionsTotal ?? 0) > 0)
                <a href="{{ route('dashboard.quizzes.questions.export.txt', $quiz) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-200 transition" download>
                    <i class="fas fa-file-alt text-[10px]"></i>
                    Questions TXT
                </a>
                @endif
                @if(($approvedQuestionsTotal ?? 0) > 0 || ($unapprovedPoolsTotal ?? 0) > 0)
                <a href="{{ route('dashboard.quizzes.questions.export.full-pool-txt', $quiz) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-200 transition" download>
                    <i class="fas fa-download text-[10px]"></i>
                    Full pool
                </a>
                @endif
                <div class="relative min-w-[12rem] flex-1 sm:flex-none sm:w-56">
                    <label for="questions-search" class="sr-only">Search questions</label>
                    <input type="text" id="questions-search" placeholder="Filter questions…" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-1.5 text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-gray-900 focus:border-gray-900" autocomplete="off">
                </div>
                @if($unapprovedPoolsTotal > 0 && !$quiz->hasStarted())
                <form action="{{ route('dashboard.quizzes.approve-all-pool', $quiz) }}" method="post" class="inline" onsubmit="return confirm('This will approve ALL {{ $unapprovedPoolsTotal }} pending questions. Continue?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-gray-900 hover:bg-gray-800 rounded-xl transition">
                        Approve all ({{ $unapprovedPoolsTotal }})
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</section>

@if(!$quiz->is_published && !$quiz->hasEnoughApprovedQuestions() && $unapprovedPoolsTotal > 0)
    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-4 flex items-start gap-3 shadow-sm">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-900 text-white"><i class="fas fa-check text-xs"></i></span>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900">Approve generated questions</p>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ $unapprovedPoolsTotal }} waiting in the pool. Need at least {{ $neededCount }} approved (currently {{ $approvedCount }}).
            </p>
        </div>
    </div>
@endif
@if(!$quiz->is_published && !$quiz->hasEnoughApprovedQuestions() && $unapprovedPoolsTotal === 0 && $shortBy > 0)
    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-4 flex items-start gap-3 shadow-sm">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"><i class="fas fa-exclamation text-xs"></i></span>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900">Need {{ $shortBy }} more question(s) to publish</p>
            <p class="text-xs text-gray-500 mt-0.5 mb-3">
                Requires {{ $neededCount }} questions. You have {{ $approvedCount }} approved and none waiting in the pool.
            </p>
            <p class="text-xs text-gray-500 mb-2">You can:</p>
            <ul class="text-xs text-gray-500 list-disc list-inside space-y-1 mb-3">
                <li><strong class="text-gray-700">Add {{ $shortBy }} more:</strong> Generate with AI below, then approve.</li>
                <li><strong class="text-gray-700">Use {{ $approvedCount }}:</strong> Edit quiz and set questions per student to {{ $approvedCount }}.</li>
            </ul>
            <div class="flex flex-wrap gap-2">
                @php
                    $generateTopicsStr = $quiz->topics;
                    if (is_string($generateTopicsStr)) {
                        $dec = json_decode($generateTopicsStr, true);
                        $generateTopicsStr = is_array($dec) ? implode(', ', array_column($dec, 'name')) : 'General knowledge';
                    }
                    if (empty(trim((string) $generateTopicsStr))) {
                        $generateTopicsStr = 'General knowledge';
                    }
                @endphp

                <div id="ai-batch-wrap-{{ $quiz->id }}">
                    <button type="button"
                        id="ai-batch-btn-{{ $quiz->id }}"
                        class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold rounded-xl text-white bg-gray-900 hover:bg-gray-800 disabled:opacity-60"
                        onclick="startAiBatchGeneration('{{ $quiz->id }}','{{ route('dashboard.quizzes.ai-generate', $quiz) }}','{{ $generateTopicsStr }}',{{ $quiz->number_of_questions }})">
                        <i class="fas fa-wand-magic-sparkles text-[10px]"></i>
                        Generate with AI
                    </button>
                    <div id="ai-batch-progress-{{ $quiz->id }}" class="mt-2 hidden">
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-4 h-4 text-gray-600 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            <span id="ai-batch-status-{{ $quiz->id }}" class="text-sm text-gray-700 font-medium">Starting…</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                            <div id="ai-batch-bar-{{ $quiz->id }}" class="bg-gray-900 h-1.5 rounded-full transition-all duration-300" style="width:0%"></div>
                        </div>
                        <p id="ai-batch-alert-{{ $quiz->id }}" class="hidden mt-2 text-sm rounded-xl px-3 py-2" role="alert"></p>
                    </div>
                </div>

                <a href="{{ route('dashboard.quizzes.edit', $quiz) }}" class="inline-flex items-center px-3.5 py-1.5 text-xs font-medium rounded-xl text-gray-700 bg-white border border-gray-200 hover:bg-gray-50">Edit quiz</a>
            </div>

<script>
function startAiBatchGeneration(quizId, batchUrl, topics, target) {
    var btn = document.getElementById('ai-batch-btn-' + quizId);
    var progressWrap = document.getElementById('ai-batch-progress-' + quizId);
    var statusEl = document.getElementById('ai-batch-status-' + quizId);
    var barEl = document.getElementById('ai-batch-bar-' + quizId);
    var alertEl = document.getElementById('ai-batch-alert-' + quizId);
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    btn.disabled = true;
    progressWrap.classList.remove('hidden');
    statusEl.textContent = 'Connecting to AI…';
    statusEl.className = 'text-sm text-indigo-700 font-medium';
    if (alertEl) {
        alertEl.classList.add('hidden');
        alertEl.textContent = '';
    }

    var isFirst = true;

    function extractApiError(data, status) {
        if (!data || typeof data !== 'object') {
            return status >= 500
                ? 'Server error (HTTP ' + status + '). Try again or contact support.'
                : 'Could not generate questions (HTTP ' + status + ').';
        }
        if (data.error) return data.error;
        if (data.message && data.success === false) return data.message;
        if (data.errors) {
            var flat = [];
            Object.keys(data.errors).forEach(function(k) {
                var v = data.errors[k];
                if (Array.isArray(v)) flat = flat.concat(v);
                else flat.push(String(v));
            });
            if (flat.length) return flat.join(' ');
        }
        return null;
    }

    function parseJsonResponse(r) {
        return r.text().then(function(text) {
            var data = {};
            if (text) {
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    var msg = 'Unexpected server response.';
                    if (r.status === 419 || (text && text.indexOf('Page Expired') !== -1)) {
                        msg = 'Session expired. Refresh the page and try again.';
                    } else if (r.status === 401 || r.status === 403) {
                        msg = 'You are not signed in or lack permission. Refresh and sign in again.';
                    } else if (r.status >= 500) {
                        msg = 'Server error (HTTP ' + r.status + '). Try again or contact support.';
                    }
                    return { ok: false, status: r.status, data: { success: false, error: msg } };
                }
            }
            return { ok: r.ok, status: r.status, data: data };
        });
    }

    function showAlert(msg, isError) {
        if (!alertEl) return;
        alertEl.textContent = msg;
        alertEl.classList.remove('hidden', 'bg-red-50', 'text-red-800', 'border', 'border-red-200', 'bg-green-50', 'text-green-800', 'border-green-200');
        if (isError) {
            alertEl.classList.add('bg-red-50', 'text-red-800', 'border', 'border-red-200');
        } else {
            alertEl.classList.add('bg-green-50', 'text-green-800', 'border', 'border-green-200');
        }
    }

    function showError(msg) {
        statusEl.textContent = 'Generation failed';
        statusEl.className = 'text-sm text-red-600 font-medium';
        barEl.classList.add('bg-red-500');
        barEl.classList.remove('bg-indigo-600', 'bg-green-500');
        showAlert(msg, true);
        btn.disabled = false;
    }

    function showSuccess(msg, soFar) {
        barEl.style.width = '100%';
        barEl.classList.add('bg-green-500');
        barEl.classList.remove('bg-indigo-600', 'bg-red-500');
        statusEl.textContent = 'Generation complete';
        statusEl.className = 'text-sm text-green-700 font-medium';
        showAlert(msg || ('Done! ' + soFar + ' question(s) in pool. Refreshing…'), false);
    }

    function runBatch() {
        var body = new URLSearchParams({ target: target, topics: topics, first_call: isFirst ? '1' : '0', _token: csrfToken });
        isFirst = false;

        fetch(batchUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body
        })
            .then(parseJsonResponse)
            .then(function(res) {
                var data = res.data || {};
                if (!res.ok || data.success === false) {
                    showError(extractApiError(data, res.status) || 'AI question generation failed. Check Settings → AI.');
                    return;
                }
                if (data.error) {
                    showError(data.error);
                    return;
                }

                var got = data.generated || 0;
                var soFar = data.total_so_far != null ? data.total_so_far : 0;
                var pct = Math.min(100, Math.round((soFar / target) * 100));
                barEl.style.width = pct + '%';
                barEl.classList.remove('bg-red-500', 'bg-green-500');
                barEl.classList.add('bg-indigo-600');

                if (data.done) {
                    if (soFar < 1) {
                        showError('AI finished but no questions were created. Check the DeepSeek API key in Settings → AI and account balance.');
                        return;
                    }
                    showSuccess(data.message || ('Done! ' + soFar + ' of ' + target + ' questions in pool.'), soFar);
                    setTimeout(function() { window.location.reload(); }, 1400);
                    return;
                }

                statusEl.textContent = data.message || ('Generated ' + soFar + ' of ' + target + ' questions (' + pct + '%)…');
                statusEl.className = 'text-sm text-indigo-700 font-medium';
                if (got > 0 && alertEl) {
                    showAlert(data.message || ('Batch saved: ' + got + ' question(s).'), false);
                }
                setTimeout(runBatch, 400);
            })
            .catch(function() {
                showError('Network error. Check your connection and try again.');
            });
    }

    runBatch();
}
</script>
        </div>
    </div>
@endif

@if($quiz->is_published)
    @php
        $quizWindowOpen = !$quiz->starts_at || $quiz->starts_at->isPast();
        $showEndQuiz = $quizWindowOpen && (!$quiz->ends_at || $quiz->ends_at->isFuture());
        $quizEnded = $quiz->hasEnded();
    @endphp
    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            @if(!$quizEnded && $quiz->link_token)
            @php $shareUrl = route('student.rules.show.quiz', ['token' => $quiz->link_token]); @endphp
            <span class="text-xs font-medium text-gray-500 shrink-0">Token</span>
            <input type="text" readonly value="{{ $quiz->link_token }}" id="quiz-token-{{ $quiz->id }}" class="w-36 text-xs font-mono font-semibold text-gray-800 bg-gray-50 border border-gray-200 rounded-xl px-2.5 py-1.5 cursor-pointer focus:ring-2 focus:ring-gray-900" title="Click Copy to copy" />
            <button type="button" data-quiz-copy-from="quiz-token-{{ $quiz->id }}" class="quiz-copy-btn inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-xl text-white bg-gray-900 hover:bg-gray-800">Copy</button>
            <details class="text-xs ml-1">
                <summary class="cursor-pointer text-gray-500 hover:text-gray-800 font-medium">Share link</summary>
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    <input type="text" readonly value="{{ $shareUrl }}" id="quiz-share-url-{{ $quiz->id }}" class="flex-1 min-w-0 max-w-xs text-xs font-mono text-gray-600 bg-gray-50 border border-gray-200 rounded-xl px-2.5 py-1.5" title="Copy with button" />
                    <button type="button" data-quiz-copy-from="quiz-share-url-{{ $quiz->id }}" class="quiz-copy-btn inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-xl text-gray-700 bg-white border border-gray-200 hover:bg-gray-50">Copy</button>
                </div>
            </details>
            <span class="flex-1"></span>
            @endif
            @if($quiz->hasStarted() && !$quiz->hasEnded())
            <div class="flex items-center gap-2">
                <form action="{{ route('dashboard.quizzes.extend-time', $quiz) }}" method="post" class="inline flex items-center gap-1" onsubmit="return confirm('Extend quiz time? This will add time to all active student sessions.');">
                    @csrf
                    <input type="number" name="additional_minutes" min="1" max="120" value="10" required class="w-16 text-xs border border-gray-200 rounded-xl px-1.5 py-1" placeholder="min">
                    <button type="submit" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-xl text-white bg-gray-900 hover:bg-gray-800">Extend</button>
                </form>
            </div>
            @endif
            @if($showEndQuiz)
            <form action="{{ route('dashboard.quizzes.end', $quiz) }}" method="post" class="inline" onsubmit="return confirm('End this quiz now? Students will no longer be able to start or submit.');">
                @csrf
                <button type="submit" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-xl text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-100">End quiz</button>
            </form>
            @elseif($quizEnded)
            <span class="text-xs font-medium text-gray-500 py-0.5 px-2">Quiz ended — link unavailable. Sessions, scores, and analytics still work.</span>
            @else
            <form action="{{ route('dashboard.quizzes.unpublish', $quiz) }}" method="post" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-xl text-gray-700 bg-white border border-gray-200 hover:bg-gray-50">Unpublish</button>
            </form>
            @endif
        </div>
    </div>
@else
    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500"><i class="fas fa-eye-slash text-xs"></i></span>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Not published</p>
                    <p class="text-xs text-gray-500 mt-0.5">Students can’t see this quiz until you publish.</p>
                    @if(!$quiz->hasEnoughApprovedQuestions())
                        <p class="text-xs text-amber-700 font-medium mt-1">Need {{ $quiz->getQuestionsPerStudent() }} approved (currently {{ $quiz->questions->count() }})</p>
                    @endif
                </div>
            </div>
            <div class="flex flex-col items-end gap-2">
                <form action="{{ route('dashboard.quizzes.publish', $quiz) }}" method="post" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3.5 py-1.5 text-xs font-semibold rounded-xl {{ $quiz->hasEnoughApprovedQuestions() ? 'text-white bg-gray-900 hover:bg-gray-800' : 'text-gray-400 bg-gray-100 cursor-not-allowed' }}" @if(!$quiz->hasEnoughApprovedQuestions()) disabled onclick="event.preventDefault(); alert('Please approve at least {{ $quiz->getQuestionsPerStudent() }} questions first. Scroll down to see the \'Approve All\' button.');" @endif>Publish</button>
                </form>
                @if(!$quiz->hasEnoughApprovedQuestions() && $unapprovedPoolsTotal > 0)
                    <p class="text-[11px] text-gray-500 text-right">Approve All ({{ $unapprovedPoolsTotal }}) below</p>
                @endif
            </div>
        </div>
    </div>
@endif

@if($quiz->is_active && !$quiz->hasEnoughApprovedQuestions())
    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-4 flex gap-3 shadow-sm">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"><i class="fas fa-lock text-xs"></i></span>
        <div class="text-xs text-gray-600">
            <p class="text-sm font-semibold text-gray-900">Locked until approval is complete</p>
            <p class="mt-0.5">Students can’t take this quiz until at least {{ $quiz->getQuestionsPerStudent() }} question(s) are approved. Currently: {{ $quiz->questions->count() }}.</p>
        </div>
    </div>
@endif

<div class="grid gap-4">
    @if($unapprovedPools->isNotEmpty())
    <section class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-gray-900 tracking-tight">Question pool</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $unapprovedPoolsTotal }} awaiting approval</p>
            </div>
            <div class="w-full sm:w-auto sm:min-w-[200px]">
                <label for="pool-search" class="sr-only">Search pool</label>
                <input type="text" id="pool-search" placeholder="Filter pool…" class="w-full text-sm py-1.5 px-3 border border-gray-200 rounded-xl bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-gray-900 focus:border-gray-900" autocomplete="off">
            </div>
            @if(!$quiz->hasStarted())
            <form action="{{ route('dashboard.quizzes.approve-all-pool', $quiz) }}" method="post" class="inline" onsubmit="return confirm('This will approve ALL {{ $unapprovedPoolsTotal }} pending questions. Continue?');">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold rounded-xl text-white bg-gray-900 hover:bg-gray-800">
                    Approve all ({{ $unapprovedPoolsTotal }})
                </button>
            </form>
            @endif
        </div>
        <div class="space-y-3" id="pool-questions-list">
            @foreach($unapprovedPools as $idx => $pool)
                @php
                    $poolOptTexts = is_array($pool->options ?? null) ? implode(' ', array_column($pool->options, 'text')) : '';
                    $poolSearchText = implode(' ', array_filter([$pool->question_text ?? '', $pool->topic ?? '', $poolOptTexts]));
                @endphp
                <div class="border border-gray-100 rounded-xl p-3.5 bg-white flex flex-wrap items-start justify-between gap-3 pool-question-row" data-search="{{ strtolower(strip_tags($poolSearchText)) }}">
                    <div class="flex-1 min-w-0">
                        @php
                            $poolRawText = trim((string)($pool->question_text ?? ''));
                            if ($poolRawText !== '') {
                                $poolQuestionText = $pool->question_text;
                            } else {
                                $poolCorrect = collect($pool->options ?? [])->firstWhere('key', $pool->correct_answer);
                                $poolCorrectText = $poolCorrect['text'] ?? '';
                                $poolQuestionText = !empty($pool->topic) ? 'Question about ' . $pool->topic : 'Question (text not available)';
                                if ($poolCorrectText !== '') {
                                    $poolQuestionText .= ' — Correct: ' . $poolCorrectText;
                                }
                            }
                        @endphp
                        <p class="text-gray-900 mb-2">{{ $poolQuestionText }}</p>
                        @if($pool->options)
                            <ul class="text-sm text-gray-600 space-y-1 mb-2">
                                @foreach($pool->options as $opt)
                                    <li><span class="font-medium">{{ $opt['key'] ?? '' }}.</span> {{ $opt['text'] ?? '' }} @if(($opt['key'] ?? '') === $pool->correct_answer)<span class="text-success-600 font-medium"> (correct)</span>@endif</li>
                                @endforeach
                            </ul>
                        @endif
                        @if($pool->topic)
                            <span class="inline-flex px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">{{ $pool->topic }}</span>
                        @endif
                        @php $poolType = \App\Support\QuestionTypes::labels()[\App\Support\QuestionTypes::normalize($pool->type ?? 'mcq')] ?? 'MCQ'; @endphp
                        <span class="inline-flex px-2 py-1 text-xs rounded-full bg-indigo-50 text-indigo-700 ml-1">{{ $poolType }}</span>
                        @if(\App\Support\QuestionTypes::normalize($pool->type ?? 'mcq') === 'fill_in' && $pool->correct_answer)
                            <p class="text-sm text-gray-600 mt-2"><span class="font-medium">Expected answer:</span> {{ $pool->correct_answer }}</p>
                        @endif
                    </div>
                    @if(!$quiz->hasStarted())
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="{{ route('dashboard.quizzes.pool.edit', [$quiz, $pool]) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">Edit</a>
                        <form action="{{ route('dashboard.quizzes.pool.approve', [$quiz, $pool]) }}" method="post" class="inline">@csrf<button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700">Approve</button></form>
                        <form action="{{ route('dashboard.quizzes.pool.reject', [$quiz, $pool]) }}" method="post" class="inline" onsubmit="return confirm('Remove this question from the pool?');">@csrf @method('DELETE')<button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700">Reject</button></form>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
        @if($unapprovedPools->hasPages())
        <div class="mt-6 flex justify-center">{{ $unapprovedPools->appends(['tab' => 'overview'])->links() }}</div>
        @endif
    </section>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm">
        @if($approvedQuestions->isEmpty())
            <div class="text-center py-10">
                <p class="text-sm font-medium text-gray-600 mb-1">No approved questions yet</p>
                @if($unapprovedPoolsTotal > 0)
                    <p class="text-xs text-gray-400">{{ $unapprovedPoolsTotal }} waiting in the pool — approve them above.</p>
                @else
                    <p class="text-xs text-gray-400">Add questions manually or generate them with AI.</p>
                @endif
            </div>
        @else
            <div class="mb-3">
                <h2 class="text-sm font-semibold text-gray-900 tracking-tight">Approved questions</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $approvedQuestions->count() }} in this quiz</p>
            </div>
            <div class="space-y-3" id="approved-questions-list">
                @foreach($approvedQuestions as $idx => $q)
                    @php
                        $rawText = trim((string)($q->text ?? ''));
                        $correctOption = $q->options && is_array($q->options) ? collect($q->options)->firstWhere('key', $q->correct_answer) : null;
                        $correctText = $correctOption['text'] ?? '';
                        if ($rawText !== '') {
                            $questionText = $q->text;
                        } else {
                            $questionText = !empty($q->topic) ? 'Question about ' . $q->topic : 'Question (text not available)';
                            if ($correctText !== '') {
                                $questionText .= ' — Correct: ' . $correctText;
                            }
                        }
                        $qSearchText = implode(' ', array_filter([$questionText, $q->topic ?? '', $q->type ?? '', $q->source ?? '']));
                    @endphp
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-primary-300 transition-colors approved-question-row" data-search="{{ strtolower(strip_tags($qSearchText)) }}">
                        <div class="flex items-start gap-3 mb-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-700 font-semibold text-sm">{{ $idx + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-900 mb-2">{{ $questionText }}</p>
                                @if($q->options && is_array($q->options) && $rawText !== '')
                                    @if($correctText)
                                        <p class="text-gray-700 text-sm mb-3">{{ $correctText }}</p>
                                    @endif
                                @endif
                                <div class="flex items-center gap-3 text-xs flex-wrap">
                                    <span class="inline-flex px-2 py-1 rounded-full bg-gray-100 text-gray-700">{{ ucfirst($q->type) }}</span>
                                    <span class="inline-flex px-2 py-1 rounded-full @if($q->source === 'ai') bg-primary-100 text-primary-700 @else bg-gray-100 text-gray-700 @endif">{{ ucfirst($q->source) }}</span>
                                    @if($q->topic)<span class="text-gray-500">• {{ $q->topic }}</span>@endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 justify-end">
                            @if(!$quiz->hasStarted())
                            <a href="{{ route('dashboard.quizzes.questions.edit', [$quiz, $q]) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">Edit</a>
                            <form action="{{ route('dashboard.quizzes.questions.destroy', [$quiz, $q]) }}" method="post" class="inline" onsubmit="return confirm('Remove this question from the quiz?');">@csrf @method('DELETE')<button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700">Delete</button></form>
                            @else
                            <span class="text-xs text-gray-500">Locked</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

<script>
(function() {
    function copyFromInput(inputEl) {
        if (!inputEl || !inputEl.value) return false;
        try {
            inputEl.focus();
            inputEl.setSelectionRange(0, inputEl.value.length);
            return document.execCommand('copy');
        } catch (e) { return false; }
    }
    function copyViaTempTextarea(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.cssText = 'position:fixed;top:0;left:0;width:2px;height:2px;padding:0;border:0;opacity:0.01;z-index:-1;';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try {
            var ok = document.execCommand('copy');
            document.body.removeChild(ta);
            return ok;
        } catch (e) {
            try { document.body.removeChild(ta); } catch (e2) {}
            return false;
        }
    }
    function showDone(btn) {
        if (!btn) return;
        var orig = btn.textContent;
        btn.textContent = 'Copied!';
        btn.classList.add('bg-green-600', 'hover:bg-green-700');
        setTimeout(function() { btn.textContent = orig; btn.classList.remove('bg-green-600', 'hover:bg-green-700'); }, 2000);
    }
    function doCopy(text, btn, sourceEl) {
        if (!text) return;
        if (sourceEl && copyFromInput(sourceEl)) {
            showDone(btn);
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() { showDone(btn); }).catch(function() {
                if (copyViaTempTextarea(text)) showDone(btn);
            });
        } else {
            if (copyViaTempTextarea(text)) showDone(btn);
        }
    }
    document.querySelectorAll('.quiz-copy-btn').forEach(function(btn) {
        var id = btn.getAttribute('data-quiz-copy-from');
        if (!id) return;
        var el = document.getElementById(id);
        if (!el) return;
        btn.addEventListener('click', function() {
            doCopy(el.value, btn, el);
        });
    });
})();
</script>
