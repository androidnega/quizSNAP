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

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
