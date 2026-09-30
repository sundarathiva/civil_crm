<?php

namespace App\Models;

use App\Support\Options;
use App\Support\Works;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'project_id', 'project_location_id', 'report_date', 'site_engineer_id',
    'work_type', 'work_id', 'work_description', 'progress', 'worker_count',
    'issues', 'remarks', 'status', 'review_note', 'reviewed_by', 'reviewed_at',
])]
class DailyReport extends Model
{
    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'progress' => 'decimal:1',
            'reviewed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProjectLocation::class, 'project_location_id');
    }

    public function siteEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_engineer_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(DailyReportMaterial::class);
    }

    public function equipmentLines(): HasMany
    {
        return $this->hasMany(DailyReportEquipment::class);
    }

    public function workers(): HasMany
    {
        return $this->hasMany(DailyReportWorker::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DailyReportPhoto::class);
    }

    public function statusLabel(): string
    {
        return Options::label(Options::REPORT_STATUSES, $this->status);
    }

    public function workLabel(): string
    {
        $work = Works::find($this->work_type, $this->work_id);

        if ($work) {
            return Options::label(Options::WORK_TYPES, $this->work_type).' '.$work->code;
        }

        return Options::label(Options::WORK_TYPES, $this->work_type);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }
}
