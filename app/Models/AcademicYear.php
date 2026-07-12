<?php

namespace App\Models;

use App\Support\AcademicCatalogScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    public $timestamps = false;

    protected $fillable = ['year', 'is_active', 'faculty_id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function academicClasses(): HasMany
    {
        return $this->hasMany(AcademicClass::class, 'academic_year_id');
    }

    public static function active(?User $user = null): ?self
    {
        $q = static::query()->where('is_active', true);
        AcademicCatalogScope::apply($q, $user ?? auth()->user(), 'academic_years');

        return $q->first();
    }

    public static function ordered(?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $q = static::query()->orderBy('year', 'desc');
        AcademicCatalogScope::apply($q, $user ?? auth()->user(), 'academic_years');

        return $q->get();
    }
}
