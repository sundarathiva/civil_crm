<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'code', 'name', 'mobile', 'worker_type', 'skill',
    'project_id', 'project_location_id', 'daily_wage', 'status',
])]
class Worker extends Model
{
    protected function casts(): array
    {
        return [
            'daily_wage' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProjectLocation::class, 'project_location_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkerAssignment::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(WorkerAttendance::class);
    }
}
