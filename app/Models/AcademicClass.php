<?php

namespace App\Models;

use App\Support\AcademicCatalogScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicClass extends Model
{
    protected $table = 'academic_classes';

    protected $fillable = ['name', 'quiz_category_id', 'level_id', 'academic_year_id', 'faculty_id'];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function quizCategory(): BelongsTo
    {
        return $this->belongsTo(QuizCategory::class, 'quiz_category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(StudentLevel::class, 'level_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'academic_class_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'academic_class_id');
    }

    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class, 'academic_class_id');
    }

    public function getDisplayLabelAttribute(): string
    {
        $parts = [$this->name];
        if ($this->academicYear) {
            $parts[] = '(' . $this->academicYear->year . ')';
        }

        return implode(' ', $parts);
    }

    public static function ordered(?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $q = static::query()->with(['quizCategory', 'level', 'academicYear'])->orderBy('name');
        AcademicCatalogScope::apply($q, $user ?? auth()->user(), 'academic_classes');

        return $q->get();
    }
}
