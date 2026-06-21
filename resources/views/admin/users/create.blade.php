@extends('layouts.dashboard')

@section('title', 'Add user')
@section('dashboard_heading', 'Add user')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6 bg-slate-50/80 rounded-xl p-4 sm:p-6">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600 mb-4">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600 shrink-0">Dashboard</a>
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('dashboard.users.index') }}" class="hover:text-primary-600 shrink-0">User management</a>
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 font-medium">Add user</span>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-8 w-full min-w-0 max-w-full overflow-hidden">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 mb-6 pb-3 border-b border-slate-200">Add user</h1>

            <form action="{{ route('dashboard.users.store') }}" method="post" class="qs-form w-full min-w-0">
                @csrf
                <x-form.section title="Account details" description="System monitors get Monitoring, Operations, and Intelligence access only. Examiners and coordinators also need institution, faculty, and department." :columns="2">
                    <x-form.input name="username" label="Username" required placeholder="e.g. j.doe or jdoe" />
                    <x-form.input name="email" type="email" label="Email" optional placeholder="user@example.com" hint="Optional. Used for password reset." />
                    <x-form.input name="name" label="Display name" optional placeholder="e.g. John Doe" />
                    <div id="phone-field-for-sms" class="qs-field">
                        <label for="phone" class="qs-label">
                            <span>Phone</span>
                            <span id="phone-required-star" class="qs-label__required" style="display: none;">*</span>
                            <span class="qs-label__optional" id="phone-optional-label">(optional)</span>
                        </label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="qs-control @error('phone') qs-control--error @enderror" placeholder="e.g. 0544919953 or 233544919953">
                        <p class="qs-hint">For examiner/coordinator: used to send login by SMS when enabled in Settings.</p>
                        @error('phone')<p class="qs-error">{{ $message }}</p>@enderror
                    </div>
                    <x-form.select name="role" label="Role" required placeholder="— Select role —">
                        @foreach($creatableRoles ?? [] as $roleValue => $roleLabel)
                            @if(($canCreateSuperAdmin ?? false) || ! in_array($roleValue, ['super_admin', 'system_admin'], true))
                                <option value="{{ $roleValue }}" {{ old('role') === $roleValue ? 'selected' : '' }}>{{ $roleLabel }}</option>
                            @endif
                        @endforeach
                    </x-form.select>

                    <div id="institution-field" class="qs-field qs-field--full staff-scope-field" style="display: none;">
                        <label for="institution_id" class="qs-label"><span>Institution</span><span class="qs-label__required">*</span></label>
                        <select name="institution_id" id="institution_id" class="qs-control @error('institution_id') qs-control--error @enderror" onchange="loadFaculties()">
                            <option value="">— Select institution —</option>
                            @foreach($institutions ?? [] as $inst)
                                <option value="{{ $inst->id }}" {{ old('institution_id') == $inst->id ? 'selected' : '' }}>{{ $inst->display_name }}</option>
                            @endforeach
                        </select>
                        @error('institution_id')<p class="qs-error">{{ $message }}</p>@enderror
                    </div>

                    <div id="faculty-field" class="qs-field staff-scope-field" style="display: none;">
                        <label for="faculty_id" class="qs-label"><span>Faculty</span><span class="qs-label__required">*</span></label>
                        <select name="faculty_id" id="faculty_id" class="qs-control @error('faculty_id') qs-control--error @enderror" onchange="loadDepartments()">
                            <option value="">— Select faculty —</option>
                            @foreach($faculties ?? [] as $faculty)
                                <option value="{{ $faculty->id }}" {{ old('faculty_id') == $faculty->id ? 'selected' : '' }}>{{ $faculty->name }}</option>
                            @endforeach
                        </select>
                        <p class="qs-hint">Coordinator oversees all departments in this faculty.</p>
                        @error('faculty_id')<p class="qs-error">{{ $message }}</p>@enderror
                    </div>

                    <div id="department-field" class="qs-field staff-scope-field" style="display: none;">
                        <label for="department_id" class="qs-label">
                            <span>Department</span>
                            <span id="department-required-star" class="qs-label__required">*</span>
                        </label>
                        <select name="department_id" id="department_id" class="qs-control @error('department_id') qs-control--error @enderror">
                            <option value="">— Select department —</option>
                            @foreach($departments ?? [] as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <p class="qs-hint">Required for examiners. Pick the department this examiner belongs to.</p>
                        @error('department_id')<p class="qs-error">{{ $message }}</p>@enderror
                    </div>
                </x-form.section>

                <x-form.section id="password-section" title="Password">
                    <div id="password-fields" class="qs-section__grid qs-section__grid--2">
                        <x-form.field label="Password" name="password" hint="At least 8 characters, including one letter and one number." full>
                            <div class="flex flex-wrap items-center gap-2">
                                <input type="password" name="password" id="password" class="qs-control flex-1 min-w-0 @error('password') qs-control--error @enderror" minlength="8" autocomplete="new-password" placeholder="Min 8 characters, letters and numbers">
                                <button type="button" id="generate-password" class="btn btn-primary text-xs px-3 py-2 min-h-0">Generate</button>
                                <button type="button" id="copy-password" class="btn btn-secondary p-2 min-h-0" title="Copy password">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </div>
                        </x-form.field>
                        <x-form.input name="password_confirmation" type="password" label="Confirm password" placeholder="Re-enter password" />
                    </div>
                    <div id="sms-password-notice" class="hidden text-sm text-slate-700 bg-blue-50 border border-blue-200 rounded-lg p-4 qs-field--full">
                        Password will be generated and sent by SMS to the phone number above. Leave password fields empty.
                    </div>
                </x-form.section>

                <x-form.actions submit="Create user" :cancel="route('dashboard.users.index')" />
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function() {
    const letters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
    const digits = '23456789';
    const chars = letters + digits + '!@#$%&*';

    function generatePassword() {
        let p = '';
        p += letters[Math.floor(Math.random() * letters.length)];
        p += digits[Math.floor(Math.random() * digits.length)];
        for (let i = 0; i < 8; i++) {
            p += chars[Math.floor(Math.random() * chars.length)];
        }
        return p.split('').sort(() => Math.random() - 0.5).join('');
    }

    document.getElementById('generate-password').addEventListener('click', function() {
        const pw = generatePassword();
        document.getElementById('password').value = pw;
        document.getElementById('password_confirmation').value = pw;
    });

    document.getElementById('copy-password').addEventListener('click', function() {
        const pw = document.getElementById('password').value;
        if (!pw) return;
        navigator.clipboard.writeText(pw).then(function() {
            const btn = document.getElementById('copy-password');
            const orig = btn.innerHTML;
            btn.innerHTML = '<svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
            btn.title = 'Copied!';
            setTimeout(function() {
                btn.innerHTML = orig;
                btn.title = 'Copy password';
            }, 1500);
        });
    });

    // Show institution/faculty/department for Examiner and Coordinator; password vs SMS
    var sendSmsOnStaffCreation = {{ json_encode($sendSmsOnStaffCreation ?? false) }};
    var roleSelect = document.getElementById('role');
    var institutionField = document.getElementById('institution-field');
    var facultyField = document.getElementById('faculty-field');
    var departmentField = document.getElementById('department-field');
    var phoneField = document.getElementById('phone-field-for-sms');
    var phoneInput = document.getElementById('phone');
    var phoneRequiredStar = document.getElementById('phone-required-star');
    var passwordSection = document.getElementById('password-section');
    var passwordFields = document.getElementById('password-fields');
    var smsPasswordNotice = document.getElementById('sms-password-notice');
    var passwordInput = document.getElementById('password');
    var passwordConfirmation = document.getElementById('password_confirmation');
    if (roleSelect) {
        function toggleInstFacDept() {
            var role = roleSelect.value;
            var showStaffScope = ['examiner', 'coordinator'].indexOf(role) !== -1;
            var staffFields = document.querySelectorAll('.staff-scope-field');
            staffFields.forEach(function(el) {
                el.style.display = showStaffScope ? '' : 'none';
            });
            if (institutionField) {
                var instSelect = document.getElementById('institution_id');
                if (instSelect) instSelect.required = showStaffScope;
            }
            if (facultyField) {
                var facSelect = document.getElementById('faculty_id');
                if (facSelect) facSelect.required = showStaffScope;
            }
            if (departmentField) {
                var showDepartment = role === 'examiner';
                departmentField.style.display = (showStaffScope && showDepartment) ? '' : 'none';
                var deptSelect = document.getElementById('department_id');
                if (deptSelect) {
                    deptSelect.required = showDepartment;
                    if (!showDepartment) deptSelect.value = '';
                }
                var deptStar = document.getElementById('department-required-star');
                if (deptStar) deptStar.style.display = showDepartment ? '' : 'none';
            }
            var showStaffFields = (role === 'examiner' || role === 'coordinator');
            var useSmsPassword = sendSmsOnStaffCreation && showStaffFields;
            if (phoneField) {
                phoneField.style.display = showStaffFields ? '' : 'none';
                if (phoneRequiredStar) phoneRequiredStar.style.display = useSmsPassword ? '' : 'none';
                var phoneOptional = document.getElementById('phone-optional-label');
                if (phoneOptional) phoneOptional.style.display = useSmsPassword ? 'none' : '';
                if (phoneInput) phoneInput.required = useSmsPassword;
            }
            if (passwordSection) {
                if (passwordFields) passwordFields.style.display = useSmsPassword ? 'none' : '';
                if (smsPasswordNotice) smsPasswordNotice.classList.toggle('hidden', !useSmsPassword);
                if (passwordInput) passwordInput.required = !useSmsPassword;
                if (passwordConfirmation) passwordConfirmation.required = !useSmsPassword;
            }
        }
        roleSelect.addEventListener('change', toggleInstFacDept);
        toggleInstFacDept();
    }
})();

// AJAX: Institution → Faculty → Department cascading dropdowns
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const baseUrl = "{{ url('/') }}";
const oldInstitutionId = {{ json_encode(old('institution_id')) }};
const oldFacultyId = {{ json_encode(old('faculty_id')) }};
const oldDepartmentId = {{ json_encode(old('department_id')) }};

function loadFaculties() {
    const institutionSelect = document.getElementById('institution_id');
    const facultySelect = document.getElementById('faculty_id');
    const departmentSelect = document.getElementById('department_id');
    if (!institutionSelect || !facultySelect) return;
    const institutionId = institutionSelect.value;
    facultySelect.innerHTML = '<option value="">— Select faculty —</option>';
    if (departmentSelect) departmentSelect.innerHTML = '<option value="">— Select department —</option>';
    if (!institutionId) return;
    fetch(baseUrl + '/dashboard/institutions/' + institutionId + '/faculties', {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.faculties) {
                data.faculties.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.id;
                    opt.textContent = f.name;
                    if (oldInstitutionId == institutionId && f.id == oldFacultyId) {
                        opt.selected = true;
                        setTimeout(loadDepartments, 100);
                    }
                    facultySelect.appendChild(opt);
                });
            }
        })
        .catch(e => console.error('Error loading faculties:', e));
}

function loadDepartments() {
    const facultySelect = document.getElementById('faculty_id');
    const departmentSelect = document.getElementById('department_id');
    if (!facultySelect || !departmentSelect) return;
    const facultyId = facultySelect.value;
    departmentSelect.innerHTML = '<option value="">— Select department —</option>';
    if (!facultyId) return;
    fetch(baseUrl + '/dashboard/faculties/' + facultyId + '/departments', {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.departments) {
                data.departments.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.name;
                    if (d.id == oldDepartmentId) opt.selected = true;
                    departmentSelect.appendChild(opt);
                });
            }
        })
        .catch(e => console.error('Error loading departments:', e));
}

@if(old('institution_id'))
document.addEventListener('DOMContentLoaded', function() { loadFaculties(); });
@endif
</script>
@endpush
@endsection
