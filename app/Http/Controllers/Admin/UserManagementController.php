<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Setting;
use App\Models\User;
use App\Services\ArkeselService;
use App\Support\UserFriendlyMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    use InteractsWithAdminSession;

    /** Normalize phone to international digits (e.g. 0544919953 → 233544919953) for storage and uniqueness check. */
    private static function normalizePhone(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $phone = preg_replace('/\D/', '', trim($value));
        if ($phone === '') {
            return null;
        }
        if (strlen($phone) >= 10 && substr($phone, 0, 1) === '0') {
            $phone = '233' . substr($phone, 1);
        } elseif (strlen($phone) >= 9 && substr($phone, 0, 3) !== '233') {
            $phone = '233' . $phone;
        }
        return $phone;
    }

    public function index(): View
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        $isCoordinatorManager = $user && $user->isCoordinator() && ! $isSuperAdmin;
        $canManageAiTokens = $isSuperAdmin || $isCoordinatorManager;

        $query = User::query()->with('courses');

        if ($isCoordinatorManager) {
            $this->applyCoordinatorExaminerScope($query, $user);
        } elseif (! $isSuperAdmin && $user) {
            $query->where('id', $user->id);
        } elseif ($isSuperAdmin && $user) {
            $query->whereIn('role', [
                User::ROLE_SUPER_ADMIN,
                User::ROLE_SYSTEM_ADMIN,
                User::ROLE_EXAMINER,
                User::ROLE_COORDINATOR,
            ]);
        }

        $users = $query->with('institution')
            ->orderBy('role')
            ->orderBy('username')
            ->paginate(20);

        $institutions = Institution::orderBy('name')->get();

        return view('admin.users.index', compact(
            'users',
            'isSuperAdmin',
            'institutions',
            'canManageAiTokens',
            'isCoordinatorManager'
        ));
    }

    public function create(): View
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Only Super Admin can create users
        if (! $isSuperAdmin) {
            abort(403, UserFriendlyMessages::ADMIN_ONLY);
        }
        // All super admins share the same privileges, including creating other admins.
        $canCreateSuperAdmin = $isSuperAdmin && $user;
        
        $institutions = Institution::orderBy('name')->get();
        $faculties = collect();
        $departments = collect();
        $sendSmsOnStaffCreation = Setting::getValue(Setting::KEY_SEND_SMS_ON_STAFF_CREATION, '0') === '1';
        $creatableRoles = User::superAdminCreatableRoles();

        return view('admin.users.create', compact(
            'institutions',
            'faculties',
            'departments',
            'isSuperAdmin',
            'canCreateSuperAdmin',
            'sendSmsOnStaffCreation',
            'creatableRoles'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        
        // Only Super Admin can create users
        if (! $isSuperAdmin) {
            abort(403, UserFriendlyMessages::ADMIN_ONLY);
        }
        $creatableRoles = User::superAdminCreatableRoleKeys();
        $canCreateSuperAdmin = $isSuperAdmin && $user;

        $courseIds = $user ? $user->assignedCourseIds() : [];
        $role = $request->role;
        $isStaffRole = in_array($role, [User::ROLE_EXAMINER, User::ROLE_COORDINATOR], true);
        $isSupportAgent = $role === User::ROLE_SUPPORT_AGENT;
        $sendSmsOnStaffCreation = Setting::getValue(Setting::KEY_SEND_SMS_ON_STAFF_CREATION, '0') === '1';
        $useSmsFlow = ($isStaffRole || $isSupportAgent) && $sendSmsOnStaffCreation && ArkeselService::hasApiKey();

        $rules = [
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
            'role' => $canCreateSuperAdmin
                ? 'required|in:' . implode(',', $creatableRoles)
                : 'required|in:examiner,coordinator',
        ];
        if ($useSmsFlow || $isSupportAgent) {
            $rules['phone'] = 'required|string|max:20';
            $rules['password'] = 'nullable';
            $rules['password_confirmation'] = 'nullable';
        } else {
            $rules['password'] = ['required', 'confirmed', Password::min(8)->letters()->numbers()];
            $rules['phone'] = 'nullable|string|max:20';
        }
        if ($request->filled('phone')) {
            $rules['phone_normalized'] = [Rule::unique('users', 'phone')];
        }
        if ($isSuperAdmin) {
            $rules['institution_id'] = 'nullable|exists:institutions,id';
            $rules['faculty_id'] = 'nullable|exists:faculties,id';
            $rules['department_id'] = 'nullable|exists:departments,id';
            $rules['sms_allocation'] = 'nullable|integer|min:0';
            $rules['ai_quiz_tokens_allocation'] = 'nullable|integer|min:0';
            $rules['ai_quiz_generation_allowed'] = 'nullable|boolean';
            if ($isStaffRole) {
                $rules['institution_id'] = 'required|exists:institutions,id';
                $rules['faculty_id'] = 'required|exists:faculties,id';
                if ($role === User::ROLE_EXAMINER) {
                    $rules['department_id'] = 'required|exists:departments,id';
                } else {
                    $rules['department_id'] = 'nullable|exists:departments,id';
                }
            }
        }

        // Normalize phone for uniqueness check (DB stores normalized format); do not overwrite phone so old() keeps user input
        if ($request->filled('phone')) {
            $request->merge(['phone_normalized' => self::normalizePhone($request->phone)]);
        }
        $request->validate($rules, [
            'password.required' => 'A password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.letters' => 'The password must contain at least one letter.',
            'password.numbers' => 'The password must contain at least one number.',
            'phone.required' => $isSupportAgent
                ? 'Phone is required for support agents so they receive SMS alerts for new chats.'
                : 'Phone is required when sending login credentials by SMS (Settings → Send SMS on staff creation).',
            'phone_normalized.unique' => 'This phone number is already used by another account. Please use a different number.',
            'institution_id.required' => 'Institution is required for examiners and coordinators.',
            'faculty_id.required' => 'Faculty is required for examiners and coordinators.',
            'department_id.required' => 'Department is required for examiners.',
        ]);

        $plainPassword = null;
        if ($useSmsFlow) {
            $plainPassword = Str::password(10);
        } else {
            $plainPassword = $request->password;
        }

        $attrs = [
            'username' => $request->username,
            'name' => $request->name ?: $request->username,
            'role' => $role,
            'password' => Hash::make($plainPassword),
        ];
        if (Schema::hasColumn('users', 'email')) {
            $attrs['email'] = $request->filled('email') ? trim($request->email) : null;
        }
        if (Schema::hasColumn('users', 'phone')) {
            $phone = $request->filled('phone') ? preg_replace('/\D/', '', trim($request->phone)) : null;
            if ($phone !== null && $phone !== '') {
                if (strlen($phone) >= 10 && substr($phone, 0, 1) === '0') {
                    $phone = '233' . substr($phone, 1);
                } elseif (strlen($phone) >= 9 && substr($phone, 0, 3) !== '233') {
                    $phone = '233' . $phone;
                }
                $attrs['phone'] = $phone;
            }
        }
        if ($isSuperAdmin && $request->filled('institution_id')) {
            $attrs['institution_id'] = $request->institution_id;
        }
        if ($isSuperAdmin && $request->has('sms_allocation') && $request->input('sms_allocation') !== null && $request->input('sms_allocation') !== '') {
            $attrs['sms_allocation'] = max(0, (int) $request->sms_allocation);
        }
        if ($isSuperAdmin && in_array($role, [User::ROLE_EXAMINER, User::ROLE_COORDINATOR], true)) {
            $defaultAllocation = $role === User::ROLE_COORDINATOR ? 3 : 10;
            $attrs['ai_quiz_tokens_allocation'] = max(0, (int) ($request->ai_quiz_tokens_allocation ?? $defaultAllocation));
            $attrs['ai_quiz_generation_allowed'] = $request->boolean('ai_quiz_generation_allowed', true);
        }
        $newUser = User::create($attrs);
        if ($isSuperAdmin && $request->filled('faculty_id')) {
            $faculty = Faculty::find($request->faculty_id);
            if ($faculty) {
                $newUser->institution_id = $faculty->institution_id;
                $newUser->faculty_id = $faculty->id;
                if ($role === User::ROLE_EXAMINER && $request->filled('department_id')) {
                    $dept = Department::find($request->department_id);
                    if ($dept && $dept->faculty_id == $faculty->id) {
                        $newUser->department_id = $dept->id;
                    }
                } else {
                    $newUser->department_id = null;
                }
                $newUser->save();
            }
        }

        if ($useSmsFlow && $newUser->phone) {
            $loginUrl = 'https://quizsnap.online/login';
            $message = sprintf(
                "QuizSnap login. URL: %s Username: %s Password: %s",
                $loginUrl,
                $newUser->username,
                $plainPassword
            );
            $result = ArkeselService::sendSms($newUser->phone, $message);
            if ($result['success']) {
                return redirect()->route('dashboard.users.index')
                    ->with('success', "Account created! We've sent the login details by SMS — they're all set.");
            }
            return redirect()->route('dashboard.users.index')
                ->with('sms_failed', $result['message'] ?? 'SMS could not be sent.')
                ->with('generated_password', $plainPassword)
                ->with('created_username', $newUser->username);
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', "Account created! They can log in with the password you set.");
    }

    public function edit(Request $request, User $user): View|RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        if (! $this->isManageableStaffUser($user)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', UserFriendlyMessages::NOT_FOUND);
        }

        // Examiners can only edit themselves (coordinators may edit examiners in scope for AI tokens)
        $isCoordinatorManager = $currentUser && $currentUser->isCoordinator() && ! $isSuperAdmin;
        if (! $isSuperAdmin && ! $isCoordinatorManager && $currentUser && $currentUser->id !== $user->id) {
            abort(403, UserFriendlyMessages::PROFILE_ONLY);
        }
        if ($isCoordinatorManager && $currentUser->id !== $user->id && ! $this->canManageExaminerAiTokens($currentUser, $user)) {
            abort(403, UserFriendlyMessages::ACCESS_DENIED);
        }

        $user->load(['courses', 'institution', 'faculty', 'department']);
        $courseIds = $currentUser ? $currentUser->assignedCourseIds() : [];
        $courses = Course::where('is_archived', false)
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();
        $institutions = Institution::orderBy('name')->get();
        
        // Load faculties and departments for examiner and coordinator
        $faculties = collect();
        $departments = collect();
        $institutionIdForFaculties = $request->get('institution_id', $user->institution_id ?? $user->faculty?->institution_id);
        if ($institutionIdForFaculties) {
            $faculties = Faculty::where('institution_id', $institutionIdForFaculties)->orderBy('name')->get();
            $facultyIdForDepartments = $request->get('faculty_id', $user->faculty_id);
            if ($facultyIdForDepartments) {
                $departments = Department::where('faculty_id', $facultyIdForDepartments)->orderBy('name')->get();
            }
        }
        
        // Check if this is a profile completion flow (examiner editing themselves and missing faculty/department)
        $isProfileCompletion = $request->has('complete_profile') &&
                               !$isSuperAdmin &&
                               $currentUser &&
                               $currentUser->id === $user->id &&
                               $user->isExaminer() &&
                               (!$user->faculty_id || !$user->department_id);

        // Prefill institution when editing examiner/coordinator who has faculty but no institution_id
        $displayInstitutionId = $user->institution_id ?? $user->faculty?->institution_id;

        $isCoordinatorManager = $currentUser && $currentUser->isCoordinator() && ! $isSuperAdmin;
        $canManageExaminerAiTokens = $this->canManageExaminerAiTokens($currentUser, $user);

        return view('admin.users.edit', compact(
            'user',
            'courses',
            'institutions',
            'faculties',
            'departments',
            'isSuperAdmin',
            'isProfileCompletion',
            'displayInstitutionId',
            'isCoordinatorManager',
            'canManageExaminerAiTokens'
        ));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        if (! $this->isManageableStaffUser($user)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', UserFriendlyMessages::NOT_FOUND);
        }

        // Examiners can only edit themselves (coordinators may edit examiners in scope for AI tokens)
        $isCoordinatorManager = $currentUser && $currentUser->isCoordinator() && ! $isSuperAdmin;
        if (! $isSuperAdmin && ! $isCoordinatorManager && $currentUser && $currentUser->id !== $user->id) {
            abort(403, UserFriendlyMessages::PROFILE_ONLY);
        }
        if ($isCoordinatorManager && $currentUser->id !== $user->id && ! $this->canManageExaminerAiTokens($currentUser, $user)) {
            abort(403, UserFriendlyMessages::ACCESS_DENIED);
        }

        if ($isCoordinatorManager && $currentUser->id !== $user->id && $user->isExaminer()) {
            $request->validate([
                'ai_quiz_tokens_allocation' => 'nullable|integer|min:0',
                'ai_quiz_generation_allowed' => 'nullable|boolean',
            ]);

            if ($request->has('ai_quiz_tokens_allocation')) {
                $user->ai_quiz_tokens_allocation = max(0, (int) ($request->ai_quiz_tokens_allocation ?? 10));
            }
            if ($request->has('ai_quiz_generation_allowed')) {
                $user->ai_quiz_generation_allowed = $request->boolean('ai_quiz_generation_allowed');
            }
            $user->save();

            return redirect()->route('dashboard.users.index')
                ->with('success', UserFriendlyMessages::UPDATED);
        }
        
        // Examiners cannot change their role
        $rules = [
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
        ];
        
        // Only Super Admin can change roles
        if ($isSuperAdmin) {
            $rules['role'] = 'required|in:' . implode(',', array_merge(
                User::superAdminCreatableRoleKeys(),
                ['student', 'leader']
            ));
        }
        
        // Only Super Admin can assign institution, faculty, department, and SMS allocation (no courses)
        if ($isSuperAdmin) {
            $rules['institution_id'] = 'nullable|exists:institutions,id';
            $rules['faculty_id'] = 'nullable|exists:faculties,id';
            $rules['department_id'] = 'nullable|exists:departments,id';
            $rules['sms_allocation'] = 'nullable|integer|min:0';
            $rules['ai_quiz_tokens_allocation'] = 'nullable|integer|min:0';
            $rules['ai_quiz_generation_allowed'] = 'nullable|boolean';
            $staffRole = $request->input('role', $user->role);
            if (in_array($staffRole, [User::ROLE_EXAMINER, User::ROLE_COORDINATOR], true)) {
                $rules['institution_id'] = 'required|exists:institutions,id';
                $rules['faculty_id'] = 'required|exists:faculties,id';
                if ($staffRole === User::ROLE_EXAMINER) {
                    $rules['department_id'] = 'required|exists:departments,id';
                }
            }
        }

        // Super Admin can set/reset password for any staff (super_admin or examiner).
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::min(8)->letters()->numbers()];
        }
        
        $request->validate($rules, [
            'password.required' => 'A password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.letters' => 'The password must contain at least one letter.',
            'password.numbers' => 'The password must contain at least one number.',
        ]);

        $user->username = $request->username;
        if (Schema::hasColumn('users', 'email')) {
            $user->email = $request->filled('email') ? trim($request->email) : null;
        }
        $user->name = $request->name ?: $user->username;
        
        // Only Super Admin can change roles - examiners keep their existing role
        if ($isSuperAdmin && $request->has('role')) {
            $user->role = $request->role;
        }
        if ($user->role === User::ROLE_SYSTEM_ADMIN) {
            $user->institution_id = null;
            $user->faculty_id = null;
            $user->department_id = null;
        }
        // If examiner is updating, role is preserved via hidden input and not changed

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($isSuperAdmin) {
            $user->institution_id = $request->filled('institution_id') ? $request->institution_id : null;
            if ($request->filled('faculty_id')) {
                $faculty = Faculty::find($request->faculty_id);
                if ($faculty) {
                    $user->institution_id = $faculty->institution_id;
                }
            }
            if ($request->has('sms_allocation') && $request->input('sms_allocation') !== null && $request->input('sms_allocation') !== '') {
                $user->sms_allocation = max(0, (int) $request->sms_allocation);
                // Do not reset sms_used: remaining = allocation - used (top-up behavior)
            }
            if (($user->isExaminer() || $user->role === User::ROLE_COORDINATOR) && $request->has('ai_quiz_tokens_allocation')) {
                $defaultAllocation = $user->role === User::ROLE_COORDINATOR ? 3 : 10;
                $user->ai_quiz_tokens_allocation = max(0, (int) ($request->ai_quiz_tokens_allocation ?? $defaultAllocation));
            }
            if ($user->isExaminer() || $user->role === User::ROLE_COORDINATOR) {
                $user->ai_quiz_generation_allowed = $request->boolean('ai_quiz_generation_allowed');
            }
        }
        
        // Handle faculty and department (both Super Admin and Examiners can set)
        if ($request->filled('faculty_id')) {
            $user->faculty_id = $request->faculty_id;
            if ($user->isCoordinator() || $user->role === User::ROLE_COORDINATOR) {
                $user->department_id = null;
            } elseif ($request->filled('department_id')) {
                $department = Department::find($request->department_id);
                if ($department && $department->faculty_id == $request->faculty_id) {
                    $user->department_id = $request->department_id;
                } else {
                    $user->department_id = null;
                }
            } else {
                $user->department_id = null;
            }
        } elseif ($request->has('faculty_id') && $request->faculty_id === '') {
            $user->faculty_id = null;
            $user->department_id = null;
        }

        if (!$user->isCoordinator() && $user->role !== User::ROLE_COORDINATOR) {
            if ($request->filled('department_id') && $user->faculty_id) {
                $department = Department::find($request->department_id);
                if ($department && $department->faculty_id == $user->faculty_id) {
                    $user->department_id = $request->department_id;
                }
            } elseif ($request->has('department_id') && $request->department_id === '') {
                $user->department_id = null;
            }
        }
        
        $user->save();

        // Admin does not assign courses; coordinator assigns examiners to courses
        if ($isSuperAdmin && $user->role === User::ROLE_COORDINATOR) {
            $user->courses()->sync([]);
        }

        // If examiner is updating their own profile, redirect to profile page
        if (!$isSuperAdmin && $currentUser && $currentUser->id === $user->id) {
            return redirect()->route('dashboard.profile.show')
                ->with('success', 'Saved');
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'Saved');
    }

    /**
     * Show password prompt to view/reset examiner password.
     */
    public function showPasswordForm(User $user): View|RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        // Only Super Admin can view/reset passwords
        if (! $isSuperAdmin) {
            abort(403, UserFriendlyMessages::ADMIN_ONLY);
        }
        
        return view('admin.users.view-password', compact('user'));
    }

    /**
     * Verify admin password and generate/reset examiner password.
     * Generates a temporary password that can be viewed once.
     */
    public function viewPassword(Request $request, User $user): RedirectResponse|View
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        if (! $isSuperAdmin) {
            abort(403, UserFriendlyMessages::ADMIN_ONLY);
        }
        
        $request->validate([
            'admin_password' => 'required|string',
            'action' => 'nullable|in:generate,reset',
            'new_password' => 'nullable|string|min:8',
            'new_password_confirmation' => 'nullable|required_with:new_password|same:new_password',
        ]);
        
        // Verify admin's password
        if (!Hash::check($request->admin_password, $currentUser->password)) {
            return redirect()->back()
                ->withInput()
                ->with('error', UserFriendlyMessages::PASSWORD_INCORRECT);
        }
        
        // Generate a random password
        if ($request->input('action') === 'generate') {
            $temporaryPassword = $this->generateTemporaryPassword();
            $user->password = Hash::make($temporaryPassword);
            $user->save();
            
            return view('admin.users.view-password', [
                'user' => $user,
                'password_verified' => true,
                'temporary_password' => $temporaryPassword,
                'message' => 'A new temporary password has been generated. Copy it now - it will not be shown again!',
            ]);
        }
        
        // Reset with custom password
        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
            $user->save();
            
            return redirect()->route('dashboard.users.index')
                ->with('success', 'Reset');
        }
        
        // Show password reset form
        return view('admin.users.view-password', [
            'user' => $user,
            'password_verified' => true,
            'message' => 'Password is set. You cannot view the original password (it\'s encrypted), but you can generate a new temporary password or set a custom one below.',
        ]);
    }

    /**
     * Reset examiner password directly (without admin password verification).
     * Generates a temporary password and shows it to the admin.
     */
    public function resetPassword(User $user): RedirectResponse|View
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        
        // Only Super Admin can reset passwords
        if (! $isSuperAdmin) {
            abort(403, UserFriendlyMessages::ADMIN_ONLY);
        }
        
        $allowedForReset = [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_SYSTEM_ADMIN,
            User::ROLE_EXAMINER,
            User::ROLE_COORDINATOR,
        ];
        if (! in_array($user->role, $allowedForReset, true)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Cannot reset password for this role.');
        }
        
        // Generate a temporary password
        $temporaryPassword = $this->generateTemporaryPassword();
        $user->password = Hash::make($temporaryPassword);
        $user->save();
        
        // Revoke existing sessions
        $user->remember_token = null;
        $user->save();
        
        if (config('session.driver') === 'database' && Schema::hasColumn(config('session.table', 'sessions'), 'user_id')) {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
        
        return redirect()->route('dashboard.users.index')
            ->with('success', 'Reset')
            ->with('temp_password', $temporaryPassword)
            ->with('reset_user_id', $user->id);
    }

    /**
     * Revoke user access: clear remember_token and sessions so they must log in again.
     */
    public function revoke(User $user): RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (! $isSuperAdmin) {
            abort(403, UserFriendlyMessages::ADMIN_ONLY);
        }

        if (! $this->isManageableStaffUser($user)) {
            return redirect()->route('dashboard.users.index')
                ->with('error', UserFriendlyMessages::NOT_FOUND);
        }

        $user->remember_token = null;
        $user->save();

        // Delete sessions for this user (Laravel database driver)
        if (config('session.driver') === 'database' && Schema::hasColumn(config('session.table', 'sessions'), 'user_id')) {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', 'Revoked');
    }

    /**
     * Delete a staff user. Cannot delete super admins or yourself.
     */
    public function destroy(User $user): RedirectResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (! $isSuperAdmin) {
            abort(403, UserFriendlyMessages::ADMIN_ONLY);
        }

        if ($user->role === User::ROLE_SUPER_ADMIN) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'Administrator accounts cannot be removed this way.');
        }

        if ($user->role === User::ROLE_SYSTEM_ADMIN && $currentUser && $currentUser->id === $user->id) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'You cannot remove your own account.');
        }

        if ($currentUser && $currentUser->id === $user->id) {
            return redirect()->route('dashboard.users.index')
                ->with('error', 'You cannot remove your own account.');
        }

        $user->courses()->detach();
        $user->delete();

        return redirect()->route('dashboard.users.index')
            ->with('success', 'Deleted');
    }

    /**
     * Update AI quiz token allocation for an examiner (AJAX).
     */
    public function updateAiTokens(Request $request): \Illuminate\Http\JsonResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        $isCoordinatorManager = $currentUser && $currentUser->isCoordinator() && ! $isSuperAdmin;

        if (! $isSuperAdmin && ! $isCoordinatorManager) {
            return response()->json([
                'success' => false,
                'message' => UserFriendlyMessages::ACCESS_DENIED,
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'ai_tokens_to_add' => 'required|integer|min:0',
        ]);

        $user = User::findOrFail($request->user_id);

        if (! $this->canReceiveAiTokens($user)) {
            return response()->json([
                'success' => false,
                'message' => 'AI tokens can only be assigned to examiners and coordinators.',
            ], 422);
        }

        if (! $this->canManageExaminerAiTokens($currentUser, $user)) {
            return response()->json([
                'success' => false,
                'message' => UserFriendlyMessages::ACCESS_DENIED,
            ], 403);
        }

        $tokensToAdd = max(0, (int) $request->ai_tokens_to_add);
        $user->ai_quiz_tokens_allocation = ($user->ai_quiz_tokens_allocation ?? 0) + $tokensToAdd;
        $user->save();
        $user->refresh();

        $status = app(\App\Services\AiQuizTokenService::class)->getStatus($user);

        return response()->json([
            'success' => true,
            'allocation' => $status['allocation'],
            'used' => $status['used'],
            'remaining' => $status['remaining'],
            'message' => 'AI tokens updated successfully.',
        ]);
    }

    /**
     * Update SMS allocation for an examiner (AJAX).
     */
    public function updateSms(Request $request): \Illuminate\Http\JsonResponse
    {
        $currentUser = $this->adminUser();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (! $isSuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => UserFriendlyMessages::ADMIN_ONLY,
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'sms_allocation' => 'required|integer|min:0',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->role !== User::ROLE_EXAMINER && $user->role !== User::ROLE_COORDINATOR) {
            return response()->json([
                'success' => false,
                'message' => 'SMS allocation can only be set for examiners and coordinators.',
            ], 422);
        }

        $creditsToAdd = max(0, (int) $request->sms_allocation);
        $user->sms_allocation = ($user->sms_allocation ?? 0) + $creditsToAdd;
        $user->save();

        $user->refresh();

        return response()->json([
            'success' => true,
            'allocation' => $user->sms_allocation,
            'used' => $user->sms_used ?? 0,
            'remaining' => $user->sms_remaining,
            'message' => 'SMS allocation updated successfully.',
        ]);
    }

    private function isManageableStaffUser(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_SYSTEM_ADMIN,
            User::ROLE_EXAMINER,
            User::ROLE_COORDINATOR,
        ], true);
    }

    private function canReceiveAiTokens(User $user): bool
    {
        return $user->isExaminer() || $user->role === User::ROLE_COORDINATOR;
    }

    private function canManageExaminerAiTokens(?User $current, User $target): bool
    {
        if (! $current) {
            return false;
        }
        if ($current->isSuperAdmin()) {
            return $target->isExaminer() || $target->role === User::ROLE_COORDINATOR;
        }
        if (! $current->isCoordinator()) {
            return false;
        }
        if (! $target->isExaminer()) {
            return false;
        }

        return $this->examinerInCoordinatorScope($current, $target);
    }

    private function examinerInCoordinatorScope(User $coordinator, User $examiner): bool
    {
        if ($coordinator->faculty_id && (int) $examiner->faculty_id !== (int) $coordinator->faculty_id) {
            return false;
        }
        if ($coordinator->department_id && (int) $examiner->department_id !== (int) $coordinator->department_id) {
            return false;
        }

        return true;
    }

    private function applyCoordinatorExaminerScope($query, User $coordinator): void
    {
        $query->where('role', User::ROLE_EXAMINER);
        if ($coordinator->faculty_id) {
            $query->where('faculty_id', $coordinator->faculty_id);
        } elseif ($coordinator->department_id) {
            $query->where('department_id', $coordinator->department_id);
        }
    }

    /**
     * Generate a secure temporary password.
     */
    private function generateTemporaryPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($chars) - 1;
        
        // Ensure at least one lowercase, one uppercase, one number, one special char
        $password .= 'abcdefghijklmnopqrstuvwxyz'[random_int(0, 25)];
        $password .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[random_int(0, 25)];
        $password .= '0123456789'[random_int(0, 9)];
        $password .= '!@#$%^&*'[random_int(0, 7)];
        
        // Fill the rest randomly
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        // Shuffle to randomize position
        return str_shuffle($password);
    }
}
