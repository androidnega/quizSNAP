<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationsAlert extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'type', 'severity', 'title', 'message', 'meta', 'read_at',
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
