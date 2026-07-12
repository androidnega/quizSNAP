<?php

namespace App\Models;

use App\Support\AcademicCatalogScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLevel extends Model
{
    protected $table = 'student_levels';

    protected $fillable = ['value', 'label', 'sort_order', 'faculty_id'];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public static function ordered(?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $q = static::query()->orderBy('sort_order')->orderBy('value');
        AcademicCatalogScope::apply($q, $user ?? auth()->user(), 'student_levels');

        return $q->get();
    }
}
