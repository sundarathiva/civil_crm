<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['daily_report_id', 'material_id', 'quantity', 'posted'])]
class DailyReportMaterial extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'posted' => 'boolean',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'daily_report_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
