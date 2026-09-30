<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'equipment_id', 'project_id', 'project_location_id', 'daily_report_id',
    'used_on', 'start_time', 'end_time', 'working_hours', 'fuel_used',
    'fuel_unit', 'work_description', 'remarks', 'user_id',
])]
class EquipmentUsage extends Model
{
    protected $table = 'equipment_usage';

    protected function casts(): array
    {
        return [
            'used_on' => 'date',
            'working_hours' => 'decimal:2',
            'fuel_used' => 'decimal:2',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProjectLocation::class, 'project_location_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'daily_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
