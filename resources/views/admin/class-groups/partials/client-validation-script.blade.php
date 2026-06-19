<script>
window.initClassGroupFormValidation = function(form) {
    if (!form) return;

    var errorBox = document.getElementById('form-client-errors');
    var errorList = document.getElementById('form-client-errors-list');
    var courseRows = document.getElementById('course-rows');

    function fieldLabel(el) {
        if (!el) return 'This field';
        if (el.id) {
            var byFor = document.querySelector('label[for="' + el.id + '"]');
            if (byFor) return byFor.textContent.replace(/\*/g, '').trim();
        }
        var wrap = el.closest('.course-row, div');
        if (wrap) {
            var lbl = wrap.querySelector('label');
            if (lbl) return lbl.textContent.replace(/\*/g, '').trim();
        }
        return el.name || 'This field';
    }

    function clearClientErrors() {
        if (errorBox) errorBox.classList.add('hidden');
        if (errorList) errorList.innerHTML = '';
        form.querySelectorAll('.is-invalid').forEach(function(el) {
            el.classList.remove('is-invalid');
        });
        if (courseRows) {
            courseRows.querySelectorAll('.course-row.is-invalid').forEach(function(row) {
                row.classList.remove('is-invalid');
            });
        }
    }

    function showClientErrors(messages, focusEl) {
        if (!errorBox || !errorList) return;
        errorList.innerHTML = '';
        messages.forEach(function(msg) {
            var li = document.createElement('li');
            li.textContent = msg;
            errorList.appendChild(li);
        });
        errorBox.classList.remove('hidden');
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (focusEl && typeof focusEl.focus === 'function') {
            setTimeout(function() { focusEl.focus(); }, 300);
        }
    }

    function validateForm() {
        var messages = [];
        var firstFocus = null;

        function addIssue(el, message) {
            if (messages.indexOf(message) === -1) messages.push(message);
            if (el) {
                el.classList.add('is-invalid');
                if (!firstFocus) firstFocus = el;
            }
        }

        var name = form.querySelector('[name="name"]');
        if (name && !name.value.trim()) addIssue(name, fieldLabel(name) + ' is required.');

        var levelSel = document.getElementById('level_id');
        if (levelSel && !levelSel.value) addIssue(levelSel, fieldLabel(levelSel) + ' is required.');

        var semesterSel = document.getElementById('semester_id');
        if (semesterSel && !semesterSel.value) addIssue(semesterSel, fieldLabel(semesterSel) + ' is required.');

        var yearSel = document.getElementById('academic_year_id');
        if (yearSel && !yearSel.value) addIssue(yearSel, fieldLabel(yearSel) + ' is required.');

        var rows = courseRows ? courseRows.querySelectorAll('.course-row') : [];
        var completeRows = 0;

        rows.forEach(function(row, index) {
            var courseSelect = row.querySelector('.course-select');
            var examinerSelect = row.querySelector('.examiner-select');
            if (!courseSelect || !examinerSelect) return;

            var rowNum = rows.length > 1 ? ' (row ' + (index + 1) + ')' : '';
            var courseName = courseSelect.options[courseSelect.selectedIndex];
            courseName = courseName && courseName.value ? courseName.textContent.trim() : 'the selected course';

            if (!courseSelect.value) {
                addIssue(courseSelect, 'Select a course' + rowNum + '.');
                row.classList.add('is-invalid');
                return;
            }

            var lecturerOptions = Array.prototype.filter.call(examinerSelect.options, function(opt) {
                return opt.value;
            });

            if (lecturerOptions.length === 0) {
                addIssue(examinerSelect, 'No lecturers available for ' + courseName + '. Assign lecturers in Courses first.');
                row.classList.add('is-invalid');
                return;
            }

            if (!examinerSelect.value) {
                addIssue(examinerSelect, 'Select a lecturer for ' + courseName + rowNum + '.');
                row.classList.add('is-invalid');
                return;
            }

            completeRows++;
        });

        if (rows.length && completeRows === 0 && messages.length === 0) {
            addIssue(null, 'Add at least one course with a lecturer.');
        }

        return {
            valid: messages.length === 0,
            messages: messages,
            focusEl: firstFocus
        };
    }

    form.addEventListener('submit', function(e) {
        clearClientErrors();
        var result = validateForm();

        if (!result.valid) {
            e.preventDefault();
            showClientErrors(result.messages, result.focusEl);
            return;
        }

        if (!form.checkValidity()) {
            e.preventDefault();
            var firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.classList.add('is-invalid');
                showClientErrors([fieldLabel(firstInvalid) + ' is invalid or missing.'], firstInvalid);
                firstInvalid.reportValidity();
            }
        }
    });

    form.addEventListener('input', function(e) {
        if (e.target.matches('input, select, textarea')) {
            e.target.classList.remove('is-invalid');
            if (errorBox && !errorBox.classList.contains('hidden')) {
                clearClientErrors();
            }
        }
    });

    form.addEventListener('change', function(e) {
        if (e.target.matches('input, select, textarea')) {
            e.target.classList.remove('is-invalid');
            var row = e.target.closest('.course-row');
            if (row) row.classList.remove('is-invalid');
        }
    });

    var serverBox = document.getElementById('server-validation-errors');
    if (serverBox) {
        var serverMessages = [];
        serverBox.querySelectorAll('li').forEach(function(li) {
            if (li.textContent.trim()) serverMessages.push(li.textContent.trim());
        });
        if (!serverMessages.length) {
            serverBox.querySelectorAll('p').forEach(function(p) {
                if (p.textContent.trim()) serverMessages.push(p.textContent.trim());
            });
        }
        if (serverMessages.length) {
            showClientErrors(serverMessages, form.querySelector(':invalid, .is-invalid') || form.querySelector('[name="name"]'));
        }
        serverBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};
</script>
