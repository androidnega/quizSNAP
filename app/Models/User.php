<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_SYSTEM_ADMIN = 'system_admin';
    public const ROLE_EXAMINER = 'examiner';
    public const ROLE_COORDINATOR = 'coordinator';

    /** @deprecated Legacy role value; treated as super_admin for access checks. */
    public const ROLE_LEGACY_ADMIN = 'admin';

    protected $fillable = [
        'username', 'email', 'phone', 'index_number', 'name', 'course_id', 'role', 'password',
        'avatar', 'institution_id', 'sms_allocation', 'ai_quiz_tokens_allocation', 'ai_quiz_generation_allowed',
        'faculty_id', 'department_id', 'coordinator',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'sms_allocation' => 'integer',
            'sms_used' => 'integer',
            'ai_quiz_tokens_allocation' => 'integer',
            'ai_quiz_tokens_used' => 'integer',
            'ai_quiz_tokens_reset_at' => 'datetime',
            'ai_quiz_generation_allowed' => 'boolean',
            'coordinator' => 'boolean',
        ];
    }

    public function getSmsRemainingAttribute(): int
    {
        $alloc = (int) ($this->attributes['sms_allocation'] ?? 0);
        $used = (int) ($this->attributes['sms_used'] ?? 0);

        return max(0, $alloc - $used);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_user')->withTimestamps();
    }

    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class, 'examiner_id');
    }

    public function classGroupsTeaching(): BelongsToMany
    {
        return $this->belongsToMany(ClassGroup::class, 'class_group_course', 'examiner_id', 'class_group_id');
    }

    public function isCoordinator(): bool
    {
        return $this->role === self::ROLE_COORDINATOR
            || (bool) ($this->attributes['coordinator'] ?? false)
            || $this->isSuperAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_LEGACY_ADMIN], true);
    }

    public function isSystemAdministrator(): bool
    {
        return $this->role === self::ROLE_SYSTEM_ADMIN;
    }

    public function canAccessMonitoring(): bool
    {
        return $this->isSuperAdmin() || $this->isSystemAdministrator();
    }

    public function canAccessOperations(): bool
    {
        return $this->isSuperAdmin() || $this->isSystemAdministrator();
    }

    public function canAccessIntelligence(): bool
    {
        return $this->isSuperAdmin() || $this->isSystemAdministrator();
    }

    /** Full access to Monitoring, Operations, and Intelligence centers. */
    public function canAccessEnterpriseCenters(): bool
    {
        return $this->canAccessMonitoring()
            && $this->canAccessOperations()
            && $this->canAccessIntelligence();
    }

    /** Administrator (Super Admin) and System Monitor only — not examiner or coordinator. */
    public function canAccessEnterpriseBroadcasting(): bool
    {
        return $this->isSuperAdmin() || $this->isSystemAdministrator();
    }

    public function isExaminer(): bool
    {
        return $this->role === self::ROLE_EXAMINER;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_LEGACY_ADMIN,
            self::ROLE_SYSTEM_ADMIN,
            self::ROLE_EXAMINER,
            self::ROLE_COORDINATOR,
        ], true);
    }

    public static function monitoringRoleLabels(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_SYSTEM_ADMIN => 'System Administrator',
            self::ROLE_EXAMINER => 'Examiner',
            self::ROLE_COORDINATOR => 'Coordinator',
        ];
    }

    /** Roles a Super Admin can assign when creating or editing staff accounts. */
    public static function superAdminCreatableRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Admin',
            self::ROLE_SYSTEM_ADMIN => 'System Monitor',
            self::ROLE_EXAMINER => 'Examiner',
            self::ROLE_COORDINATOR => 'Coordinator',
        ];
    }

    public static function superAdminCreatableRoleKeys(): array
    {
        return array_keys(self::superAdminCreatableRoles());
    }

    public function assignedCourseIds(): array
    {
        if ($this->isSuperAdmin() || $this->isCoordinator()) {
            return Course::where('is_archived', false)->pluck('id')->all();
        }

        $ids = $this->courses()
            ->where('is_archived', false)
            ->pluck('courses.id')
            ->all();

        // Per-class-group lecturer assignments (class_group_course.examiner_id)
        if (\Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id')) {
            $pivotIds = \Illuminate\Support\Facades\DB::table('class_group_course')
                ->join('courses', 'courses.id', '=', 'class_group_course.course_id')
                ->where('class_group_course.examiner_id', $this->id)
                ->where('courses.is_archived', false)
                ->distinct()
                ->pluck('class_group_course.course_id')
                ->all();
            $ids = array_values(array_unique(array_merge($ids, $pivotIds)));
        }

        return $ids;
    }

    public static function coordinatorWithSmsBalanceForClassGroup(ClassGroup $classGroup): ?self
    {
        $classGroup->load('examiner');
        $examinerFacultyId = $classGroup->examiner?->faculty_id;

        $q = self::query()
            ->where(function ($q) {
                $q->where('role', self::ROLE_COORDINATOR)
                    ->orWhere('role', self::ROLE_SUPER_ADMIN)
                    ->orWhere('coordinator', true);
            })
            ->whereRaw('(COALESCE(sms_allocation, 0) - COALESCE(sms_used, 0)) > 0');

        $q->where(function ($q) use ($examinerFacultyId) {
            $q->whereNull('faculty_id');
            if ($examinerFacultyId !== null) {
                $q->orWhere('faculty_id', $examinerFacultyId);
            }
        });

        return $q->first();
    }

    public function classGroupIds(): array
    {
        if ($this->isSuperAdmin()) {
            return ClassGroup::pluck('id')->all();
        }
        if ($this->isCoordinator()) {
            $q = ClassGroup::query();
            if ($this->faculty_id) {
                $q->whereHas('examiner', fn ($e) => $e->where('faculty_id', $this->faculty_id));
            } elseif ($this->department_id) {
                $q->whereHas('examiner', fn ($e) => $e->where('department_id', $this->department_id));
            }

            return $q->pluck('id')->all();
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id')) {
            return \Illuminate\Support\Facades\DB::table('class_group_course')
                ->where('examiner_id', $this->id)
                ->distinct()
                ->pluck('class_group_id')
                ->all();
        }

        return $this->classGroups()->pluck('id')->all();
    }

    public function examinersInScope(): \Illuminate\Database\Eloquent\Builder
    {
        $q = User::where('role', self::ROLE_EXAMINER)->orderBy('name');
        if ($this->isSuperAdmin()) {
            return $q;
        }
        if ($this->isCoordinator() && $this->faculty_id) {
            return $q->where('faculty_id', $this->faculty_id);
        }
        if ($this->department_id) {
            return $q->where('department_id', $this->department_id);
        }

        return $q;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }
        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return asset('storage/' . $this->avatar);
    }
}
