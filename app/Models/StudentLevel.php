<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentLevel extends Model
{
    protected $table = 'student_levels';

    protected $fillable = ['value', 'label', 'sort_order'];

    public static function ordered(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('sort_order')->orderBy('value')->get();
    }
}
