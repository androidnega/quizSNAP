<?php

namespace App\Models;

use App\Services\MailConfigService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model implements Authenticatable
{
    protected $table = 'students';

    protected $hidden = [
        'password',
    ];

    protected $fillable = [
        'index_number',
        'index_number_hash',
        'phone_contact',
        'student_name',
        'email',
        'level',
        'department_id',
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
    ];

    /**
     * Completely remove a student (by index) from QuizSnap.
     */
    public static function deleteEverywhereByIndex(string $indexNumber): void
    {
        $indexUpper = strtoupper(trim($indexNumber));
        if ($indexUpper === '') {
            return;
        }

        $hash = self::hashIndexNumber($indexUpper);

        \App\Models\QuizSession::whereRaw('UPPER(TRIM(student_index)) = ?', [$indexUpper])->delete();
        \App\Models\QuizAcceptance::whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])->delete();
        \App\Models\Otp::where('index_number_hash', $hash)->delete();
        self::where('index_number_hash', $hash)->delete();
    }

    /**
     * Find student by index number using hash index (avoids UPPER/TRIM table scans).
     */
    public static function findByIndex(?string $index, array $columns = ['*']): ?self
    {
        $hash = self::hashIndexNumber($index);
        if ($hash === hash('sha256', '')) {
            return null;
        }

        return self::where('index_number_hash', $hash)->first($columns);
    }

    /**
     * Normalize index for hashing and comparison (trim + lowercase).
     */
    public static function normalizeIndex(?string $index): string
    {
        return $index !== null ? strtolower(trim($index)) : '';
    }

    /**
     * SHA-256 hash of normalized index number. Use for lookups; store in index_number_hash.
     */
    public static function hashIndexNumber(?string $index): string
    {
        return hash('sha256', self::normalizeIndex($index));
    }

    /**
     * Normalize phone for storage/comparison: digits only; Ghana local (0...) becomes 233...
     * Accepts +233, 233, or 0... formats.
     */
    public static function normalizePhoneForStorage(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) >= 10 && $digits[0] === '0') {
            return '233'.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Whether user input is a plausible phone number (digits and phone formatting only; no letters).
     */
    public static function isValidPhoneInput(?string $raw): bool
    {
        if ($raw === null || trim($raw) === '') {
            return false;
        }
        $trimmed = trim($raw);
        if (preg_match('/[a-zA-Z]/', $trimmed)) {
            return false;
        }
        if (! preg_match('/^[\d\s+\-().]+$/', $trimmed)) {
            return false;
        }
        $digits = preg_replace('/\D/', '', $trimmed);

        return strlen($digits) >= 9 && strlen($digits) <= 15;
    }

    /**
     * Find a student by phone (digits only). Tries exact, 0-prefix, and 233 (Ghana) prefix.
     */
    public static function findByPhone(string $digitsOnly): ?self
    {
        if ($digitsOnly === '') {
            return null;
        }
        $normalized = ltrim($digitsOnly, '0') ?: $digitsOnly;
        $candidates = array_unique([
            $digitsOnly,
            $normalized,
            '0' . $normalized,
            '233' . $normalized,
        ]);
        if (strlen($digitsOnly) >= 12 && str_starts_with($digitsOnly, '233')) {
            $candidates[] = '0' . substr($digitsOnly, 3);
        }
        return self::whereIn('phone_contact', $candidates)->first();
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->id;
    }

    public function getAuthPassword(): string
    {
        return (string) ($this->attributes['password'] ?? '');
    }

    public function hasPassword(): bool
    {
        $p = $this->attributes['password'] ?? null;

        return $p !== null && $p !== '';
    }

    /** Minimum length for student account passwords (no letter/number/symbol rules). */
    public const PASSWORD_MIN_LENGTH = 4;

    /**
     * @return list<string>
     */
    public static function passwordValidationRules(bool $confirmed = true): array
    {
        $rules = ['required', 'string', 'min:' . self::PASSWORD_MIN_LENGTH];
        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function passwordValidationMessages(): array
    {
        return [
            'password.min' => 'Password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters.',
            'password.confirmed' => 'Passwords do not match.',
        ];
    }

    /** When admin enables student password login (Settings → OTP). */
    public static function isPasswordLoginEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_STUDENT_PASSWORD_LOGIN_ENABLED, '1') === '1';
    }

    /** Allow returning students to sign in with SMS instead of password (Settings → OTP). */
    public static function isOtpReturnLoginEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_STUDENT_OTP_RETURN_LOGIN_ENABLED, '0') === '1';
    }

    public static function isEmailRequired(): bool
    {
        return Setting::getValue(Setting::KEY_STUDENT_EMAIL_REQUIRED, '1') === '1';
    }

    public static function isPasswordResetEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_STUDENT_PASSWORD_RESET_ENABLED, '1') === '1'
            && MailConfigService::isConfigured();
    }

    public function hasEmail(): bool
    {
        return trim((string) ($this->email ?? '')) !== '';
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->hasPhone() && $this->phone_verified_at !== null;
    }

    public function needsEmailCollection(): bool
    {
        return self::isEmailRequired() && ! $this->hasEmail();
    }

    public function needsPhoneVerification(): bool
    {
        return ! $this->hasVerifiedPhone();
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    /** Class group memberships (this index in various groups). */
    public function classGroupStudents(): HasMany
    {
        return $this->hasMany(ClassGroupStudent::class, 'index_number', 'index_number');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Quiz sessions where this student (by index) took a quiz. */
    public function quizSessions(): HasMany
    {
        return $this->hasMany(QuizSession::class, 'student_index', 'index_number');
    }

    /** WebAuthn passkeys (fingerprint / Face ID) for this student. */
    public function passkeys(): HasMany
    {
        return $this->hasMany(StudentPasskey::class);
    }

    public function hasPhone(): bool
    {
        return $this->phone_contact !== null && trim($this->phone_contact) !== '';
    }

    /**
     * Students are never examiners. Used by shared dashboard layout to gate SMS UI.
     */
    public function isExaminer(): bool
    {
        return false;
    }

    /** Display name: student_name or index_number. */
    public function getDisplayNameAttribute(): string
    {
        return trim($this->student_name ?? '') !== ''
            ? $this->student_name
            : $this->index_number;
    }

    /** First name only (first word of student_name, or index_number if no name). */
    public function getFirstNameAttribute(): string
    {
        $name = trim($this->student_name ?? '');
        if ($name === '') {
            return $this->index_number;
        }
        $first = explode(' ', $name, 2)[0] ?? '';
        return $first !== '' ? $first : $this->index_number;
    }

    /** Initials for avatar placeholder (e.g. "Emmanuel Kofi" → "EK"). */
    public function getInitialsAttribute(): string
    {
        $name = trim($this->student_name ?? '');
        if ($name === '') {
            return strtoupper(substr($this->index_number, 0, 2));
        }
        $words = preg_split('/\s+/', $name, 3);
        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
}
