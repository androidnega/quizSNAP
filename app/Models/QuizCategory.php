<?php

namespace App\Models;

use App\Support\AcademicCatalogScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizCategory extends Model
{
    protected $table = 'quiz_categories';

    protected $fillable = ['name', 'sort_order', 'faculty_id'];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'quiz_category_id');
    }

    public function academicClasses(): HasMany
    {
        return $this->hasMany(AcademicClass::class, 'quiz_category_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'quiz_category_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'quiz_category_id');
    }

    public static function ordered(?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $q = static::query()->orderBy('sort_order')->orderBy('name');
        AcademicCatalogScope::apply($q, $user ?? auth()->user(), 'quiz_categories');

        return $q->get();
    }
}
