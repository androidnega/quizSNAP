<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    public $timestamps = false;

    protected $fillable = ['year', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function academicClasses(): HasMany
    {
        return $this->hasMany(AcademicClass::class, 'academic_year_id');
    }

    /** Academic years are global; $user is accepted for API compatibility. */
    public static function active($user = null): ?self
    {
        return static::query()->where('is_active', true)->first();
    }

    /** Academic years are global; $user is accepted for API compatibility. */
    public static function ordered($user = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()->orderBy('year', 'desc')->get();
    }
}
