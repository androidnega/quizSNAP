<?php

namespace App\Models;

use App\Support\AcademicCatalogScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $table = 'semesters';

    protected $fillable = ['value', 'name', 'sort_order', 'faculty_id'];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'semester_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'semester_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'semester_id');
    }

    public static function ordered(?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $q = static::query()->orderBy('sort_order');
        AcademicCatalogScope::apply($q, $user ?? auth()->user(), 'semesters');

        return $q->get();
    }
}
