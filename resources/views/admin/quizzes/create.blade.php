@extends('layouts.dashboard')

@section('title', 'Create Quiz')
@section('dashboard_heading', 'Create Quiz')

@push('styles')
<style>
#quiz-create-form .input,
#quiz-create-form input[type="text"],
#quiz-create-form input[type="number"],
#quiz-create-form input[type="datetime-local"],
#quiz-create-form select,
#quiz-create-form textarea {
    border-width: 1px;
    border-color: #e5e7eb;
    background-color: #fff;
    color: #374151;
    font-size: 1rem;
    font-weight: 400;
    padding: 0.5rem 0.75rem;
    min-height: 44px;
    border-radius: 0.5rem;
}
#quiz-create-form .input:focus,
#quiz-create-form input:focus,
#quiz-create-form select:focus,
#quiz-create-form textarea:focus {
    border-color: #93c5fd;
    outline: none;
    box-shadow: 0 0 0 2px rgba(147, 197, 253, 0.35);
}
#quiz-create-form label.block {
    font-weight: 500;
    color: #4b5563;
    font-size: 0.875rem;
}
#quiz-create-form textarea.input,
#quiz-create-form textarea {
    min-height: 6rem;
}
/* Generated prompt: placeholder-style, light text, compact size */
#generated-ai-prompt {
    color: #9ca3af !important;
    font-weight: 400 !important;
    font-size: 0.8125rem !important;
    line-height: 1.45 !important;
}
#generated-ai-prompt[data-prompt-default="true"] {
    user-select: none;
    -webkit-user-select: none;
    cursor: default;
}
#generated-ai-prompt:not([data-prompt-default="true"]) {
    cursor: pointer;
}
#ai-generate-panel {
    margin-top: 0.875rem;
    padding: 0.625rem 0.875rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.625rem;
    background: #fafafa;
}
#ai-generate-panel.ai-generating {
    border-color: #c7d2fe;
    background: #f8fafc;
}
#ai-generate-panel .ai-progress-track {
    height: 3px;
    border-radius: 9999px;
    background: #e5e7eb;
    overflow: hidden;
}
#ai-generate-panel .ai-progress-fill {
    height: 100%;
    border-radius: 9999px;
    background: #6366f1;
    transition: width 0.25s ease;
    min-width: 0;
}
#ai-generate-btn {
    min-height: 0;
}
</style>
@endpush

@section('dashboard_content')
<div class="w-full max-w-5xl mx-auto min-w-0 overflow-x-hidden">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5 md:p-8">
            @if(session('success'))
                <div class="alert alert-success mb-6">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('warning'))
                <div class="alert alert-warning mb-6">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('warning') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-error mb-6 quiz-create-feedback" role="alert">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"/>
                    </svg>
                    <strong>Error:</strong> {{ session('error') }}
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-error mb-6 quiz-create-feedback" role="alert">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul class="list-disc list-inside mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if(isset($aiTokenStatus) && $aiTokenStatus && !$aiTokenStatus['can_use'] && old('question_source', ($aiApiAvailable ?? false) ? 'ai' : 'json') === 'ai')
                <div class="alert alert-error mb-6" role="alert">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"/>
                    </svg>
                    <strong>AI unavailable:</strong> {{ $aiTokenStatus['message'] ?? 'You cannot generate questions with AI right now. Choose Paste JSON instead.' }}
                </div>
            @endif

            @php
                $canUseAi = ($aiApiAvailable ?? false) && (isset($aiTokenStatus) && ($aiTokenStatus['can_use'] ?? false));
                $defaultQuestionSource = old('question_source', $canUseAi ? 'ai' : 'json');
            @endphp

            <form action="{{ route('dashboard.quizzes.store') }}" method="post" id="quiz-create-form" class="space-y-6" enctype="multipart/form-data">
                @csrf

                <div class="mb-5">
                    <label for="title" class="block font-medium text-gray-700 mb-2">Quiz Title *</label>
                    <input type="text" id="title" name="title" required value="{{ old('title') }}" class="input @error('title') border-danger-500 @enderror" placeholder="e.g., Midterm Exam - Mathematics">
                    @error('title')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="mb-5">
                    <label for="exam_type" class="block font-medium text-gray-700 mb-2">Exam type</label>
                    <select id="exam_type" name="exam_type" class="input">
                        <option value="">— Select —</option>
                        @foreach(\App\Models\Quiz::examTypeOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('exam_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Shown on PDF score reports (e.g. Quiz, Midsem, End of Semester).</p>
                </div>

                @php
                    $oldClassGroupIds = old('class_group_ids', request('class_group_ids', old('class_group_id') ? [old('class_group_id')] : []));
                    if (! is_array($oldClassGroupIds)) {
                        $oldClassGroupIds = $oldClassGroupIds ? [(string) $oldClassGroupIds] : [];
                    }
                @endphp
                <div class="mb-5">
                    <fieldset>
                        <legend class="block font-medium text-gray-700 mb-2">Class groups *</legend>
                        <p class="text-xs text-gray-500 mb-3">Select one or more class groups assigned to you. The same exam is created for each group. Courses shared by all selected groups appear below.</p>
                        <div class="space-y-2 rounded-lg border border-gray-200 p-3 bg-gray-50/50 max-h-64 overflow-y-auto" id="class-group-checkboxes">
                            @foreach($classGroups as $g)
                                <label class="flex items-start gap-3 p-2 rounded-lg hover:bg-white cursor-pointer">
                                    <input type="checkbox" name="class_group_ids[]" value="{{ $g->id }}" class="mt-1 w-4 h-4 rounded text-primary-600 border-gray-300 focus:ring-primary-500 class-group-checkbox" data-courses="{{ $g->courses->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toJson() }}" {{ in_array((string) $g->id, array_map('strval', $oldClassGroupIds), true) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-800">
                                        <span class="font-medium">{{ $g->display_name }}</span>
                                        <span class="text-gray-500">({{ $g->students_count }} students)</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if($classGroups->isEmpty())
                            <p class="text-sm text-red-600 mt-2">No class groups with students are assigned to you yet.</p>
                        @endif
                    </fieldset>
                    @error('class_group_ids')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    @error('class_group_ids.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    <p id="quiz-create-class-group-required" class="text-sm text-red-600 mt-1 hidden">Select at least one class group.</p>
                </div>
                <div class="mb-5">
                    <label for="course_id" class="block font-medium text-gray-700 mb-2">Course *</label>
                    <select id="course_id" class="input @error('course_id') border-danger-500 @enderror">
                        <option value="">Select class group(s) first</option>
                    </select>
                    <input type="hidden" name="course_id" id="course_id_input" value="{{ old('course_id') }}">
                    <p class="text-xs text-gray-500 mt-1">Courses attached to every selected class group.</p>
                    @error('course_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                @if(isset($quizCategories) && isset($levels) && isset($semesters) && isset($academicYears))
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 mb-5 hidden" id="quizsnap-academic-context-section" aria-hidden="true">
                    <p class="text-base font-semibold text-gray-900 mb-3">Or use QuizSnap academic context</p>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="quiz_category_id" class="block font-medium text-gray-700 mb-2">Category</label>
                            <select id="quiz_category_id" name="quiz_category_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($quizCategories as $c)
                                    <option value="{{ $c->id }}" {{ old('quiz_category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="level_id" class="block font-medium text-gray-700 mb-2">Level</label>
                            <select id="level_id" name="level_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($levels as $l)
                                    <option value="{{ $l->id }}" {{ old('level_id') == $l->id ? 'selected' : '' }}>{{ $l->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="semester_id" class="block font-medium text-gray-700 mb-2">Semester</label>
                            <select id="semester_id" name="semester_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($semesters as $s)
                                    <option value="{{ $s->id }}" {{ old('semester_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="academic_year_id" class="block font-medium text-gray-700 mb-2">Academic Year</label>
                            <select id="academic_year_id" name="academic_year_id" class="input">
                                <option value="">— Select —</option>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ old('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="academic_class_id" class="block font-medium text-gray-700 mb-2">Class</label>
                            <select id="academic_class_id" name="academic_class_id" class="input">
                                <option value="">Select Category, Level, Year</option>
                            </select>
                        </div>
                        <div>
                            <label for="course_id_quizsnap" class="block font-medium text-gray-700 mb-2">Course (auto-load)</label>
                            <select id="course_id_quizsnap" class="input">
                                <option value="">Select Category, Level, Semester</option>
                            </select>
                        </div>
                    </div>
                </div>
                @endif

            <div id="quiz-create-course-required" class="alert alert-error mb-6 quiz-create-feedback {{ $errors->has('course_id') ? '' : 'hidden' }}" role="alert">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"/>
                </svg>
                <strong>Please select a course</strong> {{ $errors->has('course_id') ? $errors->first('course_id') : '(from Class Group or QuizSnap section) before creating the quiz.' }}
            </div>

                <p class="text-base font-semibold text-gray-900 mt-8 pt-5 border-t border-gray-200">Question pool &amp; per student</p>
                @include('admin.quizzes.partials.question-type-fields')
                <div class="grid md:grid-cols-2 gap-4 md:gap-6 mb-5">
                    <div>
                        <label for="number_of_questions" class="block font-medium text-gray-700 mb-2">Number of questions (pool / AI target) *</label>
                        <input type="number" id="number_of_questions" name="number_of_questions" min="1" max="250" required readonly value="{{ old('number_of_questions', 10) }}" class="input bg-gray-50 @error('number_of_questions') border-danger-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">Auto-calculated from question type counts above. Max 250.</p>
                        @error('number_of_questions')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="questions_per_student" class="block font-medium text-gray-700 mb-2">Questions per student *</label>
                        <input type="number" id="questions_per_student" name="questions_per_student" min="1" max="250" required value="{{ old('questions_per_student', 10) }}" class="input @error('questions_per_student') border-danger-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">Each student gets this many, drawn from the approved pool.</p>
                        @error('questions_per_student')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4 md:gap-6 mb-5">
                    <div>
                        <label for="duration_minutes" class="block font-medium text-gray-700 mb-2">Duration (minutes) *</label>
                        <input type="number" id="duration_minutes" name="duration_minutes" min="1" required value="{{ old('duration_minutes', 30) }}" class="input @error('duration_minutes') border-danger-500 @enderror">
                        @error('duration_minutes')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <fieldset class="mb-5 rounded-lg border border-gray-200 p-4 bg-gray-50/40">
                    <legend class="text-base font-semibold text-gray-900 px-1">How to add questions</legend>
                    <p class="text-sm text-gray-500 mb-3">Choose one method. JSON paste does not use AI tokens.</p>
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-white cursor-pointer hover:border-primary-300 {{ !$canUseAi ? 'opacity-60' : '' }}">
                            <input type="radio" name="question_source" value="ai" class="mt-1 w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500 question-source-radio" {{ $defaultQuestionSource === 'ai' ? 'checked' : '' }} {{ !$canUseAi ? 'disabled' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-900">Generate with AI (DeepSeek)</span>
                                <span class="block text-xs text-gray-500 mt-0.5">Add topics, then generate. One token only if questions are created.</span>
                                @if(!$canUseAi)
                                    <span class="block text-xs text-amber-700 mt-1">
                                        @if(!($aiGenerationEnabled ?? true))
                                            AI is disabled in Settings → AI.
                                        @elseif(!($aiApiAvailable ?? false))
                                            No DeepSeek API key configured.
                                        @else
                                            {{ $aiTokenStatus['message'] ?? 'AI is not available for your account.' }}
                                        @endif
                                    </span>
                                @endif
                            </span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-white cursor-pointer hover:border-primary-300">
                            <input type="radio" name="question_source" value="json" class="mt-1 w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500 question-source-radio" {{ $defaultQuestionSource === 'json' ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-900">Paste JSON</span>
                                <span class="block text-xs text-gray-500 mt-0.5">Import questions from external tools (ChatGPT, etc.) using the JSON format below.</span>
                            </span>
                        </label>
                    </div>
                    @error('question_source')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                </fieldset>

                <div id="section-ai-topics" class="mb-5 {{ $defaultQuestionSource === 'json' ? 'hidden' : '' }}">
                    <label for="topics-input" class="block font-medium text-gray-700 mb-2">Topics *</label>
                    <input type="hidden" name="topics" id="topics-value" value="{{ old('topics') }}">
                    <input type="text" id="topics-input" autocomplete="off" placeholder="Type a topic, press comma to add" class="input" aria-describedby="topic-tags-hint">
                    <div id="topic-tags" class="flex flex-wrap gap-2 min-h-[2rem] mt-2" role="list" aria-label="Added topics"></div>
                    <p id="topic-tags-hint" class="text-xs text-gray-500 mt-1">Add topics manually, or upload a course outline below and let AI suggest topics. Remove any you do not want.</p>
                    @error('topics')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror

                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50/80 p-4 space-y-3">
                        <p class="text-sm font-medium text-gray-800">Course outline <span class="font-normal text-gray-500">(optional)</span></p>
                        <p class="text-xs text-gray-500">Upload or paste your syllabus. AI can extract teachable topics and skip course codes, titles, and other non-topic headers.</p>
                        <div>
                            <label for="source_outline" class="block text-sm font-medium text-gray-700 mb-1">Upload file</label>
                            <input type="file" id="source_outline" name="source_outline" accept=".txt,.pdf,.docx" class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            <p class="text-xs text-gray-500 mt-1">.txt, .pdf, or .docx (max 10 MB)</p>
                        </div>
                        <div>
                            <label for="source_script" class="block text-sm font-medium text-gray-700 mb-1">Or paste outline text</label>
                            <textarea id="source_script" name="source_script" rows="4" class="input w-full min-h-[6rem] resize-y" placeholder="Paste course outline or syllabus text here…">{{ old('source_script') }}</textarea>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" id="extract-topics-btn" class="btn btn-primary px-4 py-2 text-sm inline-flex items-center gap-2" {{ !($aiApiAvailable ?? false) ? 'disabled' : '' }}>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                Extract topics with AI
                            </button>
                            <span id="extract-topics-feedback" class="text-sm text-gray-600" aria-live="polite"></span>
                        </div>
                    </div>

                    <div id="ai-generate-panel" class="mt-3">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            @if($canUseAi && isset($aiTokenStatus))
                                <span class="text-[11px] font-medium text-slate-500 tabular-nums">{{ $aiTokenStatus['remaining'] }} token{{ ($aiTokenStatus['remaining'] ?? 0) === 1 ? '' : 's' }}</span>
                            @endif
                            <button type="button" id="ai-generate-btn" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-md text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed transition-colors" {{ !$canUseAi ? 'disabled' : '' }}>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Generate questions
                            </button>
                            <span id="ai-generate-status" class="text-[11px] text-slate-500 truncate max-w-[14rem] hidden" aria-live="polite"></span>
                            <span id="ai-generate-percent" class="text-[11px] font-semibold tabular-nums text-indigo-600 hidden">0%</span>
                        </div>
                        <div id="ai-generate-progress" class="hidden mt-2" aria-live="polite">
                            <div class="ai-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="ai-generate-progressbar">
                                <div id="ai-generate-bar" class="ai-progress-fill" style="width:0%"></div>
                            </div>
                            <p id="ai-generate-counts" class="text-[10px] text-slate-500 mt-1 tabular-nums">0 / 0</p>
                        </div>
                        <p id="ai-generate-error" class="hidden text-xs text-red-600 mt-2" role="alert"></p>
                    </div>
                </div>

                <div id="section-json-prompt" class="{{ $defaultQuestionSource === 'ai' ? 'hidden' : '' }}">
                <p class="text-base font-semibold text-gray-900 mt-4 pt-4 border-t border-gray-200">Optional: copy prompt for external AI</p>
                <p class="text-sm text-gray-500 mt-1 mb-3">Add topics below and copy this prompt into ChatGPT or another tool, then paste the JSON response in the next section.</p>
                <div class="mb-5 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="mb-3">
                        <label for="json-prompt-topics-input" class="block text-sm font-medium text-gray-700 mb-1">Topics (for prompt only)</label>
                        <input type="text" id="json-prompt-topics-input" autocomplete="off" placeholder="Type a topic, press comma to add" class="input">
                        <input type="hidden" id="json-prompt-topics-value" value="">
                        <div id="json-prompt-topic-tags" class="flex flex-wrap gap-2 min-h-[2rem] mt-2" role="list"></div>
                    </div>
                    <textarea id="generated-ai-prompt" readonly rows="10" class="generated-prompt-textarea w-full rounded-lg border border-gray-200 bg-gray-50/50 px-3 py-2.5 font-mono font-normal resize-y min-h-[8.5rem] focus:ring-2 focus:ring-primary-300 focus:border-primary-300 placeholder-gray-400" style="color: #9ca3af; font-size: 0.8125rem; line-height: 1.45;" aria-label="Generated prompt — add topics to enable copy" placeholder="Add topics above to generate the prompt…" data-prompt-default="true"></textarea>
                    <div class="flex flex-wrap items-center gap-3 mt-3">
                        <button type="button" id="copy-ai-prompt-btn" class="btn btn-primary px-4 py-2 text-sm inline-flex items-center gap-2 opacity-50 cursor-not-allowed" disabled aria-label="Add topics above to enable copy">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Copy prompt
                        </button>
                        <span id="copy-ai-prompt-feedback" class="text-sm text-gray-600" aria-live="polite"></span>
                    </div>
                </div>

                <p class="text-base font-semibold text-gray-900 mt-4 pt-4 border-t border-gray-200">Paste question JSON</p>
                <p class="text-sm text-gray-600 mt-1 mb-3">Paste the JSON array here, then click Validate before creating the quiz.</p>
                <div class="mb-5">
                    <label for="ai-json-input" class="sr-only">Paste AI-generated JSON</label>
                    <textarea id="ai-json-input" name="ai_json" rows="8" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 font-mono text-sm text-gray-800 resize-y min-h-[8.5rem] @error('ai_json') border-danger-500 @enderror" placeholder='[{"type":"mcq","text":"Question?","options":{"A":"...","B":"...","C":"...","D":"..."},"correct":"A","topic":"..."}]' aria-describedby="json-validation-result json-validation-errors"></textarea>
                    @if($errors->has('ai_json'))
                        <div id="json-validation-errors" class="text-sm text-red-600 mt-1" role="alert">
                            <ul class="list-disc list-inside">
                                @foreach($errors->get('ai_json') as $err)
                                    <li>{{ is_array($err) ? implode(' ', $err) : $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div id="json-validation-result" class="text-sm hidden mt-1" aria-live="polite"></div>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <button type="button" id="validate-json-btn" class="validate-json-btn btn px-4 py-2 text-sm font-medium rounded-lg text-white bg-gray-500 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">Validate JSON</button>
                        <span id="validate-json-feedback" class="text-sm text-gray-500" aria-live="polite"></span>
                    </div>
                </div>
                </div>

                <p class="text-base font-semibold text-gray-900 mt-8 pt-5 border-t border-gray-200">Scheduling</p>
                <div class="grid md:grid-cols-2 gap-4 md:gap-6 mb-5">
                    <div>
                        <label for="starts_at" class="block font-medium text-gray-700 mb-2">Starts at (optional)</label>
                        <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at') }}" class="input">
                    </div>
                    <div>
                        <label for="ends_at" class="block font-medium text-gray-700 mb-2">Ends at (optional)</label>
                        <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at') }}" class="input">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-2 focus:ring-primary-500">
                        <span class="text-sm font-medium text-gray-900">Activate quiz immediately</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Students can access the quiz once created.</p>
                </div>

                <div class="mb-5">
                    <label for="result_visibility" class="block font-medium text-gray-700 mb-2">Result visibility</label>
                    <select id="result_visibility" name="result_visibility" class="input">
                        @foreach(\App\Models\Quiz::resultVisibilityOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('result_visibility', 'full_review_after_end') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">What students see after the quiz ends.</p>
                </div>

                <p class="text-xs text-gray-500">Allowed devices (desktop / mobile / both) are set by the coordinator on the class group.</p>

                <div class="flex flex-wrap items-center gap-3 mt-6 pt-5 border-t border-gray-200">
                    <button type="submit" class="btn px-6 py-3 rounded-lg font-semibold min-h-[48px] bg-yellow-400 hover:bg-yellow-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed" id="submit-btn" {{ $classGroups->isEmpty() && !isset($quizCategories) ? 'disabled' : '' }}>
                        Create Quiz
                    </button>
                    <p id="ai-submit-hint" class="text-xs text-slate-500 hidden w-full sm:w-auto">Use <strong>Generate questions</strong> above when AI is selected.</p>
                    <a href="{{ route('dashboard.quizzes.index') }}" class="btn px-6 py-3 rounded-lg font-semibold min-h-[48px] bg-red-600 hover:bg-red-700 text-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Cancel
                    </a>
                </div>
                @if($classGroups->isEmpty() && !isset($quizCategories))
                    <p class="text-sm text-red-600 mt-2">Create a class group or use QuizSnap academic context above first.</p>
                @endif
            </form>
    </div>
</div>
@if(session('error') || $errors->any())
<script>
(function() {
    var el = document.querySelector('.quiz-create-feedback');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
})();
</script>
@endif
@push('scripts')
<script src="{{ asset('js/quiz-question-types.js') }}"></script>
<script>
(function() {
    function getSelectedClassGroupCheckboxes() {
        return Array.from(document.querySelectorAll('input.class-group-checkbox:checked'));
    }
    var courseSelect = document.getElementById('course_id');
    var oldCourseId = @json(old('course_id'));
    var courseIdInput = document.getElementById('course_id_input');
    function parseCoursesFromCheckbox(cb) {
        try {
            return JSON.parse(cb.getAttribute('data-courses') || '[]');
        } catch (e) {
            return [];
        }
    }
    function intersectCourses(courseSets) {
        if (!courseSets.length) return [];
        return courseSets.reduce(function(acc, set) {
            if (acc === null) return set.slice();
            var ids = new Set(set.map(function(c) { return String(c.id); }));
            return acc.filter(function(c) { return ids.has(String(c.id)); });
        }, null) || [];
    }
    function updateCourses() {
        var checked = getSelectedClassGroupCheckboxes();
        courseSelect.innerHTML = '<option value="">Select course</option>';
        if (!checked.length) {
            var quizsnapEl = document.getElementById('course_id_quizsnap');
            if (!quizsnapEl || !quizsnapEl.value) { if (courseIdInput) courseIdInput.value = ''; }
            return;
        }
        var courseSets = checked.map(parseCoursesFromCheckbox);
        var courses = intersectCourses(courseSets);
        if (checked.length > 1 && courses.length === 0) {
            var emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.textContent = 'No course shared by all selected groups';
            courseSelect.appendChild(emptyOpt);
            if (courseIdInput) courseIdInput.value = '';
            return;
        }
        courses.forEach(function(c) {
            var o = document.createElement('option');
            o.value = c.id;
            o.textContent = c.name;
            if (String(c.id) === String(oldCourseId)) o.selected = true;
            courseSelect.appendChild(o);
        });
        syncCourseId();
    }
    function syncCourseId() {
        if (!courseIdInput) return;
        var v = courseSelect && courseSelect.value ? courseSelect.value : (document.getElementById('course_id_quizsnap') && document.getElementById('course_id_quizsnap').value) || '';
        courseIdInput.value = v;
    }
    document.querySelectorAll('input.class-group-checkbox').forEach(function(cb) {
        cb.addEventListener('change', function() {
            updateCourses();
            var quizsnap = document.getElementById('course_id_quizsnap');
            if (quizsnap) quizsnap.innerHTML = '<option value="">Select Category, Level, Semester</option>';
            syncCourseId();
        });
    });
    updateCourses();
    if (courseSelect) courseSelect.addEventListener('change', syncCourseId);
    var form = document.getElementById('quiz-create-form');
    if (form) form.addEventListener('submit', function(e) {
        syncCourseId();
        var quizsnap = document.getElementById('course_id_quizsnap');
        var usesQuizSnap = quizsnap && quizsnap.value;
        if (quizsnap && quizsnap.value) {
            if (courseIdInput) courseIdInput.value = quizsnap.value;
        }
        if (!usesQuizSnap) {
            var checkedGroups = getSelectedClassGroupCheckboxes();
            var groupMsg = document.getElementById('quiz-create-class-group-required');
            if (!checkedGroups.length) {
                e.preventDefault();
                if (groupMsg) { groupMsg.classList.remove('hidden'); groupMsg.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
                else { alert('Select at least one class group.'); }
                return false;
            }
            if (groupMsg) groupMsg.classList.add('hidden');
        }
        var val = courseIdInput ? courseIdInput.value.trim() : '';
        if (!val) {
            e.preventDefault();
            var msg = document.getElementById('quiz-create-course-required');
            if (msg) { msg.classList.remove('hidden'); msg.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
            else { alert('Please select a course.'); }
            return false;
        }
        document.getElementById('quiz-create-course-required') && document.getElementById('quiz-create-course-required').classList.add('hidden');
    });
})();

@if(isset($quizCategories))
(function() {
    var base = '{{ url("dashboard") }}';
    var cat = document.getElementById('quiz_category_id');
    var level = document.getElementById('level_id');
    var sem = document.getElementById('semester_id');
    var year = document.getElementById('academic_year_id');
    var cls = document.getElementById('academic_class_id');
    var courseQuizsnap = document.getElementById('course_id_quizsnap');
    var courseIdInput = document.getElementById('course_id_input');

    function loadClasses() {
        var q = [];
        if (cat && cat.value) q.push('quiz_category_id=' + cat.value);
        if (level && level.value) q.push('level_id=' + level.value);
        if (year && year.value) q.push('academic_year_id=' + year.value);
        cls.innerHTML = '<option value="">Loading...</option>';
        fetch(base + '/quizsnap/academic-classes?' + q.join('&')).then(function(r) { return r.json(); }).then(function(data) {
            cls.innerHTML = '<option value="">Select class</option>';
            (data.classes || []).forEach(function(c) {
                var o = document.createElement('option');
                o.value = c.id;
                o.textContent = c.name;
                cls.appendChild(o);
            });
        }).catch(function() { cls.innerHTML = '<option value="">Select Category, Level, Year</option>'; });
    }
    function loadCourses() {
        var q = [];
        if (cat && cat.value) q.push('quiz_category_id=' + cat.value);
        if (level && level.value) q.push('level_id=' + level.value);
        if (sem && sem.value) q.push('semester_id=' + sem.value);
        courseQuizsnap.innerHTML = '<option value="">Loading...</option>';
        fetch(base + '/quizsnap/courses?' + q.join('&')).then(function(r) { return r.json(); }).then(function(data) {
            courseQuizsnap.innerHTML = '<option value="">Select course</option>';
            (data.courses || []).forEach(function(c) {
                var o = document.createElement('option');
                o.value = c.id;
                o.textContent = c.name + (c.code ? ' (' + c.code + ')' : '');
                courseQuizsnap.appendChild(o);
            });
            if (courseIdInput) courseIdInput.value = courseQuizsnap.value || '';
        }).catch(function() { courseQuizsnap.innerHTML = '<option value="">Select Category, Level, Semester</option>'; });
    }
    function syncCourseFromQuizsnap() {
        if (courseIdInput && courseQuizsnap && courseQuizsnap.value) courseIdInput.value = courseQuizsnap.value;
    }
    if (cat) cat.addEventListener('change', function() { loadClasses(); loadCourses(); });
    if (level) level.addEventListener('change', function() { loadClasses(); loadCourses(); });
    if (sem) sem.addEventListener('change', loadCourses);
    if (year) year.addEventListener('change', loadClasses);
    if (courseQuizsnap) courseQuizsnap.addEventListener('change', syncCourseFromQuizsnap);
    var form = document.getElementById('quiz-create-form');
    if (form) form.addEventListener('submit', function() { syncCourseFromQuizsnap(); });
})();
@endif

(function() {
    var topicsValue = document.getElementById('topics-value');
    var topicsInput = document.getElementById('topics-input');
    var tagsContainer = document.getElementById('topic-tags');
    if (!topicsValue || !topicsInput || !tagsContainer) return;

    function parseTopics(str) {
        if (!str || typeof str !== 'string') return [];
        return str.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    }

    function getTags() {
        var val = topicsValue.value || '';
        return parseTopics(val);
    }

    function setTags(tags) {
        topicsValue.value = tags.join(', ');
        renderTags();
        if (window.updateGeneratedAiPrompt) window.updateGeneratedAiPrompt();
    }

    window.setAiTopicTags = function(topicsArray) {
        if (!Array.isArray(topicsArray)) return;
        var cleaned = topicsArray.map(function(t) { return String(t || '').trim(); }).filter(Boolean);
        setTags(cleaned);
    };

    function addTag(label) {
        var t = (label || '').trim();
        if (!t) return;
        var tags = getTags();
        if (tags.indexOf(t) !== -1) return;
        tags.push(t);
        setTags(tags);
    }

    function removeTag(index) {
        var tags = getTags();
        tags.splice(index, 1);
        setTags(tags);
    }

    function renderTags() {
        var tags = getTags();
        tagsContainer.innerHTML = '';
        tags.forEach(function(t, i) {
            var span = document.createElement('span');
            span.className = 'inline-flex items-center gap-1 rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary-700';
            span.setAttribute('role', 'listitem');
            span.appendChild(document.createTextNode(t));
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'inline-flex h-4 w-4 items-center justify-center rounded-full hover:bg-primary-200';
            btn.setAttribute('aria-label', 'Remove topic ' + t);
            btn.innerHTML = '<svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';
            btn.addEventListener('click', function() { removeTag(i); });
            span.appendChild(btn);
            tagsContainer.appendChild(span);
        });
    }

    topicsInput.addEventListener('keydown', function(e) {
        if (e.key === ',') {
            e.preventDefault();
            var v = topicsInput.value.trim();
            if (v) addTag(v);
            topicsInput.value = '';
            return;
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            var v = topicsInput.value.trim();
            if (v) addTag(v);
            topicsInput.value = '';
        }
    });

    topicsInput.addEventListener('blur', function() {
        var v = topicsInput.value.trim();
        if (v) {
            addTag(v);
            topicsInput.value = '';
        }
    });

    renderTags();
})();

(function() {
    var promptEl = document.getElementById('generated-ai-prompt');
    var numEl = document.getElementById('number_of_questions');
    var topicsValueEl = document.getElementById('topics-value');
    var copyBtn = document.getElementById('copy-ai-prompt-btn');
    var copyFeedback = document.getElementById('copy-ai-prompt-feedback');

    function parseTopics(str) {
        if (!str || typeof str !== 'string') return [];
        return str.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    }

    function buildGeneratedPrompt(topicsArray, count) {
        var counts = window.QuizQuestionTypes ? window.QuizQuestionTypes.getTypeCountsFromForm() : { mcq: parseInt(count, 10) || 10, true_false: 0, fill_in: 0 };
        return window.QuizQuestionTypes.buildGeneratedPrompt(topicsArray, counts);
    }

    function hasUserAddedTopics() {
        var topicsStr = getPromptTopicsStr();
        return parseTopics(topicsStr).length > 0;
    }

    function getPromptTopicsStr() {
        var jsonSection = document.getElementById('section-json-prompt');
        if (jsonSection && !jsonSection.classList.contains('hidden')) {
            var jsonVal = document.getElementById('json-prompt-topics-value');
            return jsonVal ? jsonVal.value : '';
        }
        return topicsValueEl ? topicsValueEl.value : '';
    }

    function updatePromptCopyState() {
        var canCopy = hasUserAddedTopics();
        if (promptEl) {
            promptEl.setAttribute('data-prompt-default', canCopy ? 'false' : 'true');
            promptEl.title = canCopy ? 'Click to copy' : 'Add topics above to enable copy';
        }
        if (copyBtn) {
            copyBtn.disabled = !canCopy;
            copyBtn.classList.toggle('opacity-50', !canCopy);
            copyBtn.classList.toggle('cursor-not-allowed', !canCopy);
            copyBtn.setAttribute('aria-label', canCopy ? 'Copy prompt' : 'Add topics above to enable copy');
        }
    }

    function updateGeneratedAiPrompt() {
        if (!promptEl) return;
        var topicsStr = getPromptTopicsStr();
        var count = numEl ? (numEl.value || numEl.getAttribute('value') || '10') : '10';
        var topicsArray = parseTopics(topicsStr);
        promptEl.value = buildGeneratedPrompt(topicsArray, count);
        updatePromptCopyState();
    }

    window.updateGeneratedAiPrompt = updateGeneratedAiPrompt;

    if (numEl) {
        numEl.addEventListener('input', updateGeneratedAiPrompt);
        numEl.addEventListener('change', updateGeneratedAiPrompt);
    }
    if (topicsValueEl) {
        topicsValueEl.addEventListener('input', updateGeneratedAiPrompt);
        topicsValueEl.addEventListener('change', updateGeneratedAiPrompt);
    }

    function copyPromptToClipboard() {
        if (!hasUserAddedTopics()) {
            if (copyFeedback) copyFeedback.textContent = 'Add topics above to enable copy.';
            return;
        }
        var text = promptEl ? promptEl.value : '';
        if (!text) {
            if (copyFeedback) copyFeedback.textContent = 'Add topics above to enable copy.';
            return;
        }
        function showOk() {
            if (copyFeedback) {
                copyFeedback.textContent = 'Copied!';
                copyFeedback.classList.add('text-success-600');
                setTimeout(function() { copyFeedback.textContent = ''; copyFeedback.classList.remove('text-success-600'); }, 2500);
            }
        }
        function showFail() {
            if (copyFeedback) copyFeedback.textContent = 'Copy failed — select the text above and copy manually (Ctrl+C).';
        }

        function copyFromRealTextarea() {
            if (!promptEl) return false;
            try {
                promptEl.focus();
                promptEl.setSelectionRange(0, promptEl.value.length);
                return document.execCommand('copy');
            } catch (e) { return false; }
        }

        function copyViaTempTextarea(t) {
            var ta = document.createElement('textarea');
            ta.value = t;
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

        if (copyFromRealTextarea()) {
            showOk();
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(showOk).catch(function() {
                if (copyViaTempTextarea(text)) showOk(); else showFail();
            });
        } else {
            if (copyViaTempTextarea(text)) showOk(); else showFail();
        }
    }

    if (copyBtn && promptEl) {
        copyBtn.addEventListener('click', copyPromptToClipboard);
    }
    if (promptEl) {
        promptEl.addEventListener('click', function() {
            if (hasUserAddedTopics()) copyPromptToClipboard();
        });
    }

    updateGeneratedAiPrompt();
    updatePromptCopyState();
})();

(function() {
    var aiJsonInput = document.getElementById('ai-json-input');
    var numEl = document.getElementById('number_of_questions');
    var validateBtn = document.getElementById('validate-json-btn');
    var resultEl = document.getElementById('json-validation-result');
    var feedbackEl = document.getElementById('validate-json-feedback');

    function parseJsonArray(str) {
        var s = str.trim();
        if (!s) return null;
        var start = s.indexOf('[');
        if (start !== -1) {
            var end = s.lastIndexOf(']');
            if (end !== -1 && end > start) s = s.substring(start, end + 1);
        }
        try {
            return JSON.parse(s);
        } catch (e) {
            return null;
        }
    }

    function getExpectedCount() {
        if (!numEl) return 10;
        var v = numEl.value || numEl.getAttribute('value') || '10';
        return Math.max(1, Math.min(250, parseInt(v, 10) || 10));
    }

    function validateJsonFrontend() {
        var raw = aiJsonInput ? aiJsonInput.value : '';
        var expected = getExpectedCount();
        var errors = [];
        if (!raw.trim()) {
            if (resultEl) { resultEl.className = 'text-sm hidden'; resultEl.innerHTML = ''; }
            if (feedbackEl) feedbackEl.textContent = 'Paste JSON first.';
            setValidateButtonState(validateBtn, false);
            return { valid: false, errors: ['JSON is empty.'] };
        }
        var arr = parseJsonArray(raw);
        if (!arr || !Array.isArray(arr)) {
            errors.push('Invalid JSON or not a JSON array.');
            if (resultEl) { resultEl.className = 'text-sm text-red-600'; resultEl.innerHTML = '<ul class="list-disc list-inside"><li>' + errors.join('</li><li>') + '</li></ul>'; resultEl.classList.remove('hidden'); }
            if (feedbackEl) feedbackEl.textContent = 'Invalid.';
            setValidateButtonState(validateBtn, false);
            return { valid: false, errors: errors };
        }
        var typeCounts = window.QuizQuestionTypes ? window.QuizQuestionTypes.getTypeCountsFromForm() : null;
        var validation = window.QuizQuestionTypes.validateJsonArray(arr, expected, typeCounts);
        var errors = validation.errors || [];
        if (errors.length > 0) {
            if (resultEl) { resultEl.className = 'text-sm text-red-600'; resultEl.innerHTML = '<ul class="list-disc list-inside space-y-0.5">' + errors.map(function(e) { return '<li>' + e + '</li>'; }).join('') + '</ul>'; resultEl.classList.remove('hidden'); }
            if (feedbackEl) feedbackEl.textContent = 'Validation failed.';
            setValidateButtonState(validateBtn, false);
            return { valid: false, errors: errors };
        }
        setValidateButtonState(validateBtn, true);
        if (resultEl) { resultEl.className = 'text-sm text-green-600'; resultEl.textContent = 'Valid. You can create the quiz.'; resultEl.classList.remove('hidden'); }
        if (feedbackEl) feedbackEl.textContent = 'Valid.';
        return { valid: true, errors: [] };
    }

    function setValidateButtonState(btn, valid) {
        if (!btn) return;
        if (valid) {
            btn.classList.remove('bg-gray-500', 'hover:bg-gray-600', 'focus:ring-gray-400');
            btn.classList.add('bg-green-600', 'hover:bg-green-700', 'focus:ring-green-500');
            btn.textContent = 'Valid';
        } else {
            btn.classList.remove('bg-green-600', 'hover:bg-green-700', 'focus:ring-green-500');
            btn.classList.add('bg-gray-500', 'hover:bg-gray-600', 'focus:ring-gray-400');
            btn.textContent = 'Validate JSON';
        }
    }

    if (validateBtn) {
        validateBtn.addEventListener('click', function() {
            validateJsonFrontend();
        });
    }
    if (aiJsonInput) {
        aiJsonInput.addEventListener('input', function() {
            setValidateButtonState(validateBtn, false);
        });
        aiJsonInput.addEventListener('change', function() {
            setValidateButtonState(validateBtn, false);
        });
    }
})();

(function() {
    var radios = document.querySelectorAll('.question-source-radio');
    var sectionAi = document.getElementById('section-ai-topics');
    var sectionJson = document.getElementById('section-json-prompt');
    var aiJsonInput = document.getElementById('ai-json-input');
    var topicsValue = document.getElementById('topics-value');
    var sourceOutline = document.getElementById('source_outline');
    var sourceScript = document.getElementById('source_script');

    function selectedSource() {
        var checked = document.querySelector('.question-source-radio:checked');
        return checked ? checked.value : 'json';
    }

    function syncQuestionSourceSections() {
        var source = selectedSource();
        if (sectionAi) sectionAi.classList.toggle('hidden', source !== 'ai');
        if (sectionJson) sectionJson.classList.toggle('hidden', source !== 'json');
        if (aiJsonInput) {
            if (source === 'ai') {
                aiJsonInput.removeAttribute('name');
            } else {
                aiJsonInput.setAttribute('name', 'ai_json');
            }
        }
        if (topicsValue) {
            if (source === 'json') {
                topicsValue.removeAttribute('name');
            } else {
                topicsValue.setAttribute('name', 'topics');
            }
        }
        if (sourceOutline) {
            if (source === 'json') {
                sourceOutline.removeAttribute('name');
            } else {
                sourceOutline.setAttribute('name', 'source_outline');
            }
        }
        if (sourceScript) {
            if (source === 'json') {
                sourceScript.removeAttribute('name');
            } else {
                sourceScript.setAttribute('name', 'source_script');
            }
        }
        var submitBtn = document.getElementById('submit-btn');
        var aiHint = document.getElementById('ai-submit-hint');
        if (submitBtn) submitBtn.classList.toggle('hidden', source === 'ai');
        if (aiHint) aiHint.classList.toggle('hidden', source !== 'ai');
        if (window.updateGeneratedAiPrompt) window.updateGeneratedAiPrompt();
    }

    radios.forEach(function(r) {
        r.addEventListener('change', syncQuestionSourceSections);
    });
    syncQuestionSourceSections();
})();

(function() {
    var topicsValue = document.getElementById('json-prompt-topics-value');
    var topicsInput = document.getElementById('json-prompt-topics-input');
    var tagsContainer = document.getElementById('json-prompt-topic-tags');
    if (!topicsValue || !topicsInput || !tagsContainer) return;

    function parseTopics(str) {
        if (!str || typeof str !== 'string') return [];
        return str.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    }

    function getTags() {
        return parseTopics(topicsValue.value || '');
    }

    function setTags(tags) {
        topicsValue.value = tags.join(', ');
        renderTags();
        if (window.updateGeneratedAiPrompt) window.updateGeneratedAiPrompt();
    }

    function addTag(label) {
        var t = (label || '').trim();
        if (!t) return;
        var tags = getTags();
        if (tags.indexOf(t) !== -1) return;
        tags.push(t);
        setTags(tags);
    }

    function removeTag(index) {
        var tags = getTags();
        tags.splice(index, 1);
        setTags(tags);
    }

    function renderTags() {
        var tags = getTags();
        tagsContainer.innerHTML = '';
        tags.forEach(function(t, i) {
            var span = document.createElement('span');
            span.className = 'inline-flex items-center gap-1 rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary-700';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'inline-flex h-4 w-4 items-center justify-center rounded-full hover:bg-primary-200';
            btn.innerHTML = '×';
            btn.addEventListener('click', function() { removeTag(i); });
            span.appendChild(document.createTextNode(t + ' '));
            span.appendChild(btn);
            tagsContainer.appendChild(span);
        });
    }

    topicsInput.addEventListener('keydown', function(e) {
        if (e.key === ',' || e.key === 'Enter') {
            e.preventDefault();
            var v = topicsInput.value.trim();
            if (v) addTag(v);
            topicsInput.value = '';
        }
    });
    topicsInput.addEventListener('blur', function() {
        var v = topicsInput.value.trim();
        if (v) { addTag(v); topicsInput.value = ''; }
    });
    renderTags();
})();

(function() {
    var extractBtn = document.getElementById('extract-topics-btn');
    var extractFeedback = document.getElementById('extract-topics-feedback');
    var sourceOutline = document.getElementById('source_outline');
    var sourceScript = document.getElementById('source_script');
    var courseInput = document.getElementById('course_id_input');
    if (!extractBtn) return;

    var extractUrl = @json(route('dashboard.quizzes.extract-topics'));
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var extracting = false;

    function getCourseId() {
        if (courseInput) return courseInput.value || '';
        var courseSelect = document.getElementById('course_id');
        return courseSelect ? (courseSelect.value || '') : '';
    }

    function runExtractTopics(auto) {
        if (extracting) return;
        var hasFile = sourceOutline && sourceOutline.files && sourceOutline.files.length > 0;
        var hasText = sourceScript && (sourceScript.value || '').trim() !== '';
        if (!hasFile && !hasText) {
            if (!auto && extractFeedback) extractFeedback.textContent = 'Upload a file or paste outline text first.';
            return;
        }

        extracting = true;
        extractBtn.disabled = true;
        if (extractFeedback) extractFeedback.textContent = 'Extracting topics…';

        var formData = new FormData();
        formData.append('_token', csrfToken);
        if (getCourseId()) formData.append('course_id', getCourseId());
        if (hasFile) formData.append('source_outline', sourceOutline.files[0]);
        if (hasText) formData.append('source_script', sourceScript.value.trim());

        fetch(extractUrl, { method: 'POST', body: formData, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
            .then(function(res) {
                if (!res.ok || res.data.error) {
                    if (extractFeedback) extractFeedback.textContent = res.data.error || 'Could not extract topics.';
                    return;
                }
                if (window.setAiTopicTags && Array.isArray(res.data.topics)) {
                    window.setAiTopicTags(res.data.topics);
                }
                if (extractFeedback) {
                    extractFeedback.textContent = res.data.topics.length + ' topic(s) added. Remove any you do not want.';
                }
            })
            .catch(function() {
                if (extractFeedback) extractFeedback.textContent = 'Network error. Try again.';
            })
            .finally(function() {
                extracting = false;
                extractBtn.disabled = false;
            });
    }

    extractBtn.addEventListener('click', function() { runExtractTopics(false); });
    if (sourceOutline) {
        sourceOutline.addEventListener('change', function() {
            if (sourceOutline.files && sourceOutline.files.length > 0) {
                runExtractTopics(true);
            }
        });
    }
})();

(function() {
    var form = document.getElementById('quiz-create-form');
    var generateBtn = document.getElementById('ai-generate-btn');
    var panel = document.getElementById('ai-generate-panel');
    var progressWrap = document.getElementById('ai-generate-progress');
    var progressBar = document.getElementById('ai-generate-bar');
    var progressBarWrap = document.getElementById('ai-generate-progressbar');
    var progressPct = document.getElementById('ai-generate-percent');
    var progressStatus = document.getElementById('ai-generate-status');
    var progressCounts = document.getElementById('ai-generate-counts');
    var errorEl = document.getElementById('ai-generate-error');
    var topicsValue = document.getElementById('topics-value');
    var sourceScript = document.getElementById('source_script');
    var sourceOutline = document.getElementById('source_outline');
    if (!form || !generateBtn) return;

    var generating = false;
    var pollTimer = null;
    var pollStartedAt = 0;
    var lastPollGenerated = -1;
    var stuckPollCount = 0;

    function isAiMode() {
        var checked = document.querySelector('.question-source-radio:checked');
        return checked && checked.value === 'ai';
    }

    function hasTopicsOrOutline() {
        var topics = (topicsValue && topicsValue.value || '').trim();
        var hasText = sourceScript && (sourceScript.value || '').trim() !== '';
        var hasFile = sourceOutline && sourceOutline.files && sourceOutline.files.length > 0;
        return topics !== '' || hasText || hasFile;
    }

    function syncCourseBeforeSubmit() {
        var courseIdInput = document.getElementById('course_id_input');
        var courseSelect = document.getElementById('course_id');
        var quizsnap = document.getElementById('course_id_quizsnap');
        if (quizsnap && quizsnap.value && courseIdInput) {
            courseIdInput.value = quizsnap.value;
        } else if (courseSelect && courseSelect.value && courseIdInput) {
            courseIdInput.value = courseSelect.value;
        }
    }

    function setProgress(percent, generated, target, message, failed) {
        var pct = Math.min(100, Math.max(0, parseInt(percent, 10) || 0));
        if (progressBar) progressBar.style.width = pct + '%';
        if (progressPct) {
            progressPct.textContent = pct + '%';
            progressPct.classList.remove('hidden');
        }
        if (progressBarWrap) progressBarWrap.setAttribute('aria-valuenow', String(pct));
        if (progressStatus && message) {
            progressStatus.textContent = message;
            progressStatus.classList.remove('hidden');
        }
        if (progressCounts && target > 0) {
            progressCounts.textContent = (parseInt(generated, 10) || 0) + ' / ' + target;
        }
        if (failed) {
            if (panel) panel.classList.remove('ai-generating');
            if (progressBar) progressBar.style.background = '#ef4444';
        }
    }

    function showError(msg) {
        if (errorEl) {
            errorEl.textContent = msg;
            errorEl.classList.remove('hidden');
        }
        generating = false;
        generateBtn.disabled = false;
        if (panel) panel.classList.remove('ai-generating');
    }

    function pollStatus(statusUrl, redirectUrl) {
        fetch(statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var status = data.status || 'idle';
                var target = Math.max(1, parseInt(data.target, 10) || 1);
                var generated = parseInt(data.generated, 10) || 0;
                var pct = data.percent != null ? parseInt(data.percent, 10) : Math.min(100, Math.round((generated / target) * 100));
                var msg = data.message || ('Generating… ' + generated + ' of ' + target);
                var elapsed = pollStartedAt ? (Date.now() - pollStartedAt) : 0;

                if (generated === lastPollGenerated) {
                    stuckPollCount++;
                } else {
                    stuckPollCount = 0;
                    lastPollGenerated = generated;
                }

                if (status === 'running') {
                    setProgress(pct, generated, target, msg, false);
                    if (elapsed > 120000 && generated === 0 && stuckPollCount > 8) {
                        showError('Generation is taking too long with no questions yet. Run: php artisan queue:work — or restart with ./start-local.sh');
                        if (progressStatus) progressStatus.textContent = 'Stalled';
                        return;
                    }
                    pollTimer = setTimeout(function() { pollStatus(statusUrl, redirectUrl); }, 900);
                } else if (status === 'completed') {
                    setProgress(100, generated, target, 'Done', false);
                    setTimeout(function() { window.location.href = redirectUrl; }, 350);
                } else if (status === 'failed') {
                    showError(data.message || 'Question generation failed. Try again or check Settings → AI.');
                    if (progressStatus) progressStatus.textContent = 'Generation failed';
                    if (progressWrap) progressWrap.classList.remove('hidden');
                } else if (elapsed > 90000 && generated === 0) {
                    showError('No progress detected. Ensure DeepSeek API key is set in Settings → AI, then try again.');
                } else {
                    pollTimer = setTimeout(function() { pollStatus(statusUrl, redirectUrl); }, 900);
                }
            })
            .catch(function() {
                pollTimer = setTimeout(function() { pollStatus(statusUrl, redirectUrl); }, 1500);
            });
    }

    function startGeneration() {
        if (!isAiMode() || generating) return;

        syncCourseBeforeSubmit();
        var courseIdInput = document.getElementById('course_id_input');
        if (!courseIdInput || !courseIdInput.value.trim()) {
            var msg = document.getElementById('quiz-create-course-required');
            if (msg) { msg.classList.remove('hidden'); msg.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
            else { alert('Please select a course.'); }
            return;
        }
        var quizsnap = document.getElementById('course_id_quizsnap');
        var usesQuizSnap = quizsnap && quizsnap.value;
        if (!usesQuizSnap) {
            var checkedGroups = document.querySelectorAll('input.class-group-checkbox:checked');
            var groupMsg = document.getElementById('quiz-create-class-group-required');
            if (!checkedGroups.length) {
                if (groupMsg) { groupMsg.classList.remove('hidden'); groupMsg.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
                else { alert('Select at least one class group.'); }
                return;
            }
            if (groupMsg) groupMsg.classList.add('hidden');
        }

        if (!hasTopicsOrOutline()) {
            showError('Add at least one topic, or upload/paste a course outline.');
            if (progressWrap) progressWrap.classList.add('hidden');
            return;
        }

        if (errorEl) errorEl.classList.add('hidden');
        generating = true;
        generateBtn.disabled = true;
        if (panel) panel.classList.add('ai-generating');
        if (progressWrap) progressWrap.classList.remove('hidden');
        if (progressStatus) {
            progressStatus.textContent = 'Starting…';
            progressStatus.classList.remove('hidden');
        }
        setProgress(0, 0, 0, 'Starting…', false);

        var formData = new FormData(form);
        formData.set('question_source', 'ai');

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function(r) {
                return r.json().then(function(data) {
                    return { ok: r.ok, status: r.status, data: data };
                }).catch(function() {
                    return { ok: false, status: r.status, data: { error: 'Unexpected server response.' } };
                });
            })
            .then(function(res) {
                if (!res.ok || !res.data.success) {
                    var errMsg = res.data.error || res.data.message;
                    if (!errMsg && res.data.errors) {
                        errMsg = Object.values(res.data.errors).flat().join(' ');
                    }
                    showError(errMsg || ('Could not start generation (HTTP ' + res.status + ').'));
                    if (progressWrap) progressWrap.classList.add('hidden');
                    return;
                }
                if (res.data.multiple) {
                    setProgress(100, 0, 0, res.data.message || 'Quizzes created.', false);
                    setTimeout(function() {
                        window.location.href = res.data.redirect_url || @json(route('dashboard.quizzes.index'));
                    }, 900);
                    return;
                }
                var target = res.data.target || 0;
                pollStartedAt = Date.now();
                lastPollGenerated = -1;
                stuckPollCount = 0;
                setProgress(0, 0, target, res.data.message || 'Generating questions…', false);
                pollStatus(res.data.status_url, res.data.redirect_url);
            })
            .catch(function() {
                showError('Network error. Check your connection and try again.');
            });
    }

    generateBtn.addEventListener('click', startGeneration);

    form.addEventListener('submit', function(e) {
        if (isAiMode()) {
            e.preventDefault();
            startGeneration();
        }
    });

    window.addEventListener('beforeunload', function() {
        if (pollTimer) clearTimeout(pollTimer);
    });
})();
</script>
@endpush
@endsection
