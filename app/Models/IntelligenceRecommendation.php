<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntelligenceRecommendation extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'category', 'severity', 'title', 'message', 'subject_type', 'subject_key', 'meta', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
