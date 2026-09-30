<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['daily_report_id', 'equipment_id', 'working_hours', 'fuel_used', 'remarks'])]
class DailyReportEquipment extends Model
{
    protected $table = 'daily_report_equipment';

    protected function casts(): array
    {
        return [
            'working_hours' => 'decimal:2',
            'fuel_used' => 'decimal:2',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'daily_report_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
