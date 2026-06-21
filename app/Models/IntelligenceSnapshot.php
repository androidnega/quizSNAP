<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntelligenceSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = ['snapshot_type', 'payload', 'recorded_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'recorded_at' => 'datetime',
        ];
    }
}
