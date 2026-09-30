<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'worker_id', 'project_id', 'project_location_id', 'attended_on',
    'status', 'overtime_hours', 'remarks', 'marked_by',
])]
class WorkerAttendance extends Model
{
    protected $table = 'worker_attendance';

    protected function casts(): array
    {
        return [
            'attended_on' => 'date',
            'overtime_hours' => 'decimal:2',
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

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function statusLabel(): string
    {
        return Options::label(Options::ATTENDANCE, $this->status);
    }
}
