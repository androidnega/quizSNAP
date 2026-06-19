@php
    $typeCounts = $typeCounts ?? \App\Support\QuestionTypes::normalizeCounts(null, (int) old('number_of_questions', 10));
    $includeMcq = old('include_mcq', ($typeCounts['mcq'] ?? 0) > 0 ? '1' : '1');
    $includeTf = old('include_true_false', ($typeCounts['true_false'] ?? 0) > 0);
    $includeFill = old('include_fill_in', ($typeCounts['fill_in'] ?? 0) > 0);
    $mcqCount = old('mcq_count', $typeCounts['mcq'] ?? (int) old('number_of_questions', 10));
    $tfCount = old('true_false_count', $typeCounts['true_false'] ?? 0);
    $fillCount = old('fill_in_count', $typeCounts['fill_in'] ?? 0);
@endphp

<div class="rounded-lg border border-gray-200 bg-slate-50 p-4 mb-5 {{ $colSpanClass ?? '' }}" id="question-type-section">
    <p class="text-base font-semibold text-gray-900 mb-1">Question types for generation</p>
    <p class="text-sm text-gray-500 mb-4">Choose which types to generate. Counts below must add up to the pool total.</p>
    <div class="space-y-3">
        <div class="flex flex-wrap items-center gap-4">
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="include_mcq" value="1" id="include_mcq" class="w-4 h-4 text-primary-600 border-gray-300 rounded" {{ $includeMcq ? 'checked' : '' }}>
                <span class="text-sm font-medium text-gray-800">MCQ</span>
            </label>
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="include_true_false" value="1" id="include_true_false" class="w-4 h-4 text-primary-600 border-gray-300 rounded" {{ $includeTf ? 'checked' : '' }}>
                <span class="text-sm font-medium text-gray-800">True / False</span>
            </label>
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="include_fill_in" value="1" id="include_fill_in" class="w-4 h-4 text-primary-600 border-gray-300 rounded" {{ $includeFill ? 'checked' : '' }}>
                <span class="text-sm font-medium text-gray-800">Fill in</span>
            </label>
        </div>
        <div class="grid sm:grid-cols-3 gap-4">
            <div id="mcq-count-wrap" class="{{ $includeMcq ? '' : 'hidden' }}">
                <label for="mcq_count" class="block text-sm font-medium text-gray-700 mb-1">MCQ count</label>
                <input type="number" id="mcq_count" name="mcq_count" min="0" max="250" value="{{ $mcqCount }}" class="input question-type-count">
            </div>
            <div id="true-false-count-wrap" class="{{ $includeTf ? '' : 'hidden' }}">
                <label for="true_false_count" class="block text-sm font-medium text-gray-700 mb-1">True/False count</label>
                <input type="number" id="true_false_count" name="true_false_count" min="0" max="250" value="{{ $tfCount }}" class="input question-type-count">
            </div>
            <div id="fill-in-count-wrap" class="{{ $includeFill ? '' : 'hidden' }}">
                <label for="fill_in_count" class="block text-sm font-medium text-gray-700 mb-1">Fill-in count</label>
                <input type="number" id="fill_in_count" name="fill_in_count" min="0" max="250" value="{{ $fillCount }}" class="input question-type-count">
            </div>
        </div>
        <p id="question-type-total-hint" class="text-xs text-gray-600"></p>
    </div>
</div>
