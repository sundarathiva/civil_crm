<?php

namespace App\Models;

use App\Support\Works;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'worker_id', 'project_id', 'project_location_id', 'work_type',
    'work_id', 'assigned_on', 'status',
])]
class WorkerAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'assigned_on' => 'date',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProjectLocation::class, 'project_location_id');
    }

    public function workLabel(): string
    {
        $work = Works::find($this->work_type, $this->work_id);

        return $work?->code ?? 'General site work';
    }
}
