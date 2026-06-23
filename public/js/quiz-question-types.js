(function(global) {
    'use strict';

    function el(id) { return document.getElementById(id); }

    function isChecked(id) {
        var node = el(id);
        return !!(node && node.checked);
    }

    function intVal(id, fallback) {
        var node = el(id);
        if (!node) return fallback || 0;
        var v = parseInt(node.value, 10);
        return isNaN(v) ? (fallback || 0) : Math.max(0, v);
    }

    function getTypeCountsFromForm() {
        var counts = { mcq: 0, true_false: 0, fill_in: 0 };
        if (isChecked('include_mcq')) counts.mcq = intVal('mcq_count', 0);
        if (isChecked('include_true_false')) counts.true_false = intVal('true_false_count', 0);
        if (isChecked('include_fill_in')) counts.fill_in = intVal('fill_in_count', 0);
        return counts;
    }

    function totalTypeCounts(counts) {
        return (counts.mcq || 0) + (counts.true_false || 0) + (counts.fill_in || 0);
    }

    function toggleTypeCountFields() {
        var map = [
            ['include_mcq', 'mcq-count-wrap'],
            ['include_true_false', 'true-false-count-wrap'],
            ['include_fill_in', 'fill-in-count-wrap']
        ];
        map.forEach(function(pair) {
            var wrap = el(pair[1]);
            if (wrap) wrap.classList.toggle('hidden', !isChecked(pair[0]));
        });
    }

    function syncPoolTotalFromTypes() {
        var counts = getTypeCountsFromForm();
        var total = totalTypeCounts(counts);
        var numEl = el('number_of_questions');
        if (numEl && total > 0) {
            numEl.value = String(total);
        }
        var hint = el('question-type-total-hint');
        if (hint) {
            if (total < 1) {
                hint.textContent = 'Select at least one question type with a count greater than 0.';
                hint.className = 'text-xs text-red-600';
            } else {
                hint.textContent = 'Pool total: ' + total + ' question(s) (' +
                    counts.mcq + ' MCQ, ' + counts.true_false + ' True/False, ' + counts.fill_in + ' Fill-in).';
                hint.className = 'text-xs text-gray-600';
            }
        }
        if (typeof global.updateGeneratedAiPrompt === 'function') {
            global.updateGeneratedAiPrompt();
        }
    }

    function designGuidelines() {
        return 'You are an expert assessment designer and examiner.\n'
            + 'Your task is to generate challenging, application-based exam questions from the topics provided.\n\n'
            + 'QUALITY PRIORITY (default for most questions):\n'
            + '- Prefer application of knowledge, critical thinking, problem-solving, analysis, and evaluation.\n'
            + '- Use realistic workplace, business, scientific, educational, social, or industry scenarios when appropriate.\n'
            + '- Use short case studies or situational stems so students must think before answering.\n'
            + '- Every question should require reasoning, not bare memorization.\n\n'
            + 'AVOID as the default style (do not make these the majority):\n'
            + '- Simple recall phrasing such as "Define...", "What is...", "List...", "State...", "Mention...".\n'
            + '- Questions answerable by copying a single sentence from memory without understanding.\n\n'
            + 'ALLOWED:\n'
            + '- Occasional recall or definition questions when they fit the topic, but keep them a minority.\n'
            + '- Direct factual checks when embedded in a scenario or when needed for balance.\n\n'
            + 'TARGET COGNITIVE MIX (approximate across the full set):\n'
            + '- 30% Application\n'
            + '- 40% Analysis\n'
            + '- 20% Evaluation\n'
            + '- 10% Creation / synthesis\n\n'
            + 'TARGET DIFFICULTY MIX (approximate across the full set):\n'
            + '- 20% Easy\n'
            + '- 50% Moderate\n'
            + '- 30% Difficult';
    }

    function typeAuthoringRules() {
        return 'PER-TYPE RULES:\n\n'
            + 'A. Multiple choice (MCQ)\n'
            + '- Use short case studies or realistic scenarios when possible.\n'
            + '- Exactly 4 options (A–D); only one best answer.\n'
            + '- Distractors must be plausible and test misunderstanding, not trick wording.\n\n'
            + 'B. True / false\n'
            + '- Scenario-based; test reasoning and judgment, not bare definitions.\n'
            + '- The statement should require evaluating a situation or claim.\n\n'
            + 'C. Fill-in-the-blank\n'
            + '- Context-based stems; students apply concepts to complete the statement.\n'
            + '- Expected answers should be concise (a word, phrase, or short term), not an essay.';
    }

    function buildGeneratedPrompt(topicsArray, typeCounts) {
        var topicList = topicsArray.length ? topicsArray.join(', ') : 'General knowledge';
        var counts = typeCounts || getTypeCountsFromForm();
        var total = totalTypeCounts(counts);
        if (total < 1) counts = { mcq: 1, true_false: 0, fill_in: 0 };
        total = totalTypeCounts(counts);
        var parts = [];
        if (counts.mcq > 0) parts.push(counts.mcq + ' multiple choice (MCQ) with exactly 4 options (A–D)');
        if (counts.true_false > 0) parts.push(counts.true_false + ' true/false');
        if (counts.fill_in > 0) parts.push(counts.fill_in + ' fill-in-the-blank');
        return designGuidelines() + '\n\n'
            + typeAuthoringRules() + '\n\n'
            + 'TOPICS — use ONLY these precise topics; do not add or substitute others: ' + topicList + '.\n'
            + 'Generate exactly ' + total + ' quiz questions aligned with those topics: ' + parts.join(', ') + '.\n'
            + 'Distribute questions across the listed topics. Each item must tag one listed topic.\n'
            + 'Reply with a JSON array only, no other text before or after.\n'
            + 'Each item MUST include: "type" ("mcq", "true_false", or "fill_in"), "text" (question text), "topic" (one listed topic).\n'
            + 'MCQ: "options" object with keys A,B,C,D and "correct" as one letter.\n'
            + 'True/false: "correct" as true or false (JSON boolean), "True"/"False", or A/B. Options optional.\n'
            + 'Fill-in: "correct" as the expected short answer string (no options). Use ___ in the question text for the blank.\n'
            + 'Do not include explanations.\n'
            + 'Example: [{"type":"mcq","text":"A clinic receives... Which action is best?","options":{"A":"...","B":"...","C":"...","D":"..."},"correct":"B","topic":"..."},{"type":"true_false","text":"Given the scenario...","correct":"True","topic":"..."},{"type":"fill_in","text":"After the audit, the team concluded that ___ was the root cause.","correct":"answer","topic":"..."}]';
    }

    function coerceCorrectToString(value) {
        if (value === true) return 'true';
        if (value === false) return 'false';
        if (typeof value === 'number') {
            if (value === 1) return 'true';
            if (value === 0) return 'false';
        }
        return String(value == null ? '' : value).trim();
    }

    function extractCorrectAnswer(item) {
        if ('correct' in item) return item.correct;
        if ('correctAnswer' in item) return item.correctAnswer;
        if ('answer' in item) return item.answer;
        return null;
    }

    function optionsLookLikeTrueFalse(options) {
        var texts = Object.keys(options).map(function(k) {
            var v = options[k];
            if (v && typeof v === 'object') return String(v.text || v.value || '').toLowerCase().trim();
            return String(v).toLowerCase().trim();
        });
        return texts.indexOf('true') !== -1 && texts.indexOf('false') !== -1;
    }

    function isValidTrueFalseCorrect(value) {
        if (value === undefined || value === null) return false;
        var c = coerceCorrectToString(value).toLowerCase();
        return ['true', 'false', 'a', 'b', 't', 'f', 'yes', 'no', '1', '0'].indexOf(c) !== -1;
    }

    function inferTypeFromItem(item) {
        if (item.type && String(item.type).trim() !== '') {
            var declared = normalizeType(item.type);
            var raw = String(item.type).toLowerCase().trim();
            if (declared !== 'mcq' || raw === 'mcq' || raw === 'multiple_choice' || raw === 'multiple choice') {
                return declared;
            }
        }
        var correct = extractCorrectAnswer(item);
        if (correct === true || correct === false) return 'true_false';
        var opts = item.options;
        if (opts && typeof opts === 'object') {
            var keys = Object.keys(opts).map(function(k) { return k.toUpperCase(); }).sort();
            if (keys.join(',') === 'A,B,C,D') return 'mcq';
            if (keys.join(',') === 'A,B' && optionsLookLikeTrueFalse(opts)) return 'true_false';
        }
        if (isValidTrueFalseCorrect(correct)) return 'true_false';
        if (correct !== null && correct !== undefined && typeof correct !== 'object') return 'fill_in';
        return 'mcq';
    }

    function normalizeType(type) {
        var t = String(type || 'mcq').toLowerCase().replace(/[\s\/]+/g, '_');
        if (t === 'true_false' || t === 'true-false' || t === 'truefalse' || t === 'tf' || t === 't_f' || t === 'boolean' || t === 'bool') return 'true_false';
        if (t === 'fill_in' || t === 'fill-in' || t === 'fillin' || t === 'short_answer' || t === 'shortanswer') return 'fill_in';
        return 'mcq';
    }

    function validateQuestionItem(item, index, actualCounts) {
        var errors = [];
        var idx = index + 1;
        if (!item || typeof item !== 'object') {
            return ['Question ' + idx + ': must be an object.'];
        }
        var type = inferTypeFromItem(item);
        actualCounts[type] = (actualCounts[type] || 0) + 1;
        if (!('text' in item) && !('question' in item)) {
            errors.push('Question ' + idx + ': missing "text" or "question".');
        }
        var correct = extractCorrectAnswer(item);
        if (type === 'fill_in') {
            if (correct === undefined || correct === null || coerceCorrectToString(correct) === '' || typeof correct === 'object') {
                errors.push('Question ' + idx + ': fill-in requires "correct" (expected answer).');
            }
            return errors;
        }
        if (type === 'true_false') {
            if (!isValidTrueFalseCorrect(correct)) {
                errors.push('Question ' + idx + ': true/false requires "correct" as True or False (or A/B).');
            }
            return errors;
        }
        if (!('options' in item) || typeof item.options !== 'object' || item.options === null) {
            errors.push('Question ' + idx + ': missing or invalid "options".');
        } else {
            var keys = Object.keys(item.options).sort();
            if (keys.join(',') !== 'A,B,C,D') {
                errors.push('Question ' + idx + ': options must have exactly A, B, C, D.');
            }
        }
        var c = coerceCorrectToString(correct);
        if (correct === undefined || correct === null || ['A', 'B', 'C', 'D'].indexOf(c.toUpperCase()) === -1) {
            errors.push('Question ' + idx + ': MCQ correct must be A, B, C, or D.');
        }
        return errors;
    }

    function validateJsonArray(arr, expectedCount, typeCounts) {
        var errors = [];
        var actualCounts = { mcq: 0, true_false: 0, fill_in: 0 };
        if (!arr || !Array.isArray(arr)) {
            return { valid: false, errors: ['Invalid JSON or not a JSON array.'] };
        }
        if (arr.length !== expectedCount) {
            errors.push('Number of questions is ' + arr.length + '; expected ' + expectedCount + '.');
        }
        for (var i = 0; i < arr.length; i++) {
            errors = errors.concat(validateQuestionItem(arr[i], i, actualCounts));
        }
        var expected = typeCounts || getTypeCountsFromForm();
        [['mcq', 'MCQ'], ['true_false', 'True/False'], ['fill_in', 'Fill-in']].forEach(function(pair) {
            var key = pair[0];
            var label = pair[1];
            var need = expected[key] || 0;
            if (need > 0 && (actualCounts[key] || 0) !== need) {
                errors.push('Expected ' + need + ' ' + label + ' question(s), found ' + (actualCounts[key] || 0) + '.');
            }
        });
        return { valid: errors.length === 0, errors: errors };
    }

    function initQuestionTypeUi() {
        ['include_mcq', 'include_true_false', 'include_fill_in'].forEach(function(id) {
            var node = el(id);
            if (node) node.addEventListener('change', function() {
                toggleTypeCountFields();
                syncPoolTotalFromTypes();
            });
        });
        document.querySelectorAll('.question-type-count').forEach(function(node) {
            node.addEventListener('input', syncPoolTotalFromTypes);
            node.addEventListener('change', syncPoolTotalFromTypes);
        });
        toggleTypeCountFields();
        syncPoolTotalFromTypes();
    }

    global.QuizQuestionTypes = {
        getTypeCountsFromForm: getTypeCountsFromForm,
        buildGeneratedPrompt: buildGeneratedPrompt,
        validateJsonArray: validateJsonArray,
        syncPoolTotalFromTypes: syncPoolTotalFromTypes,
        initQuestionTypeUi: initQuestionTypeUi
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initQuestionTypeUi);
    } else {
        initQuestionTypeUi();
    }
})(window);
