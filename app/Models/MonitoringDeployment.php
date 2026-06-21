<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringDeployment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'version', 'git_commit', 'branch', 'deployed_by', 'deployed_by_name',
        'notes', 'meta', 'deployed_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'deployed_at' => 'datetime',
        ];
    }

    public function deployer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'deployed_by');
    }
}
