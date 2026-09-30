<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['daily_report_id', 'worker_id', 'attendance_status'])]
class DailyReportWorker extends Model
{
    public function report(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'daily_report_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function statusLabel(): string
    {
        return Options::label(Options::ATTENDANCE, $this->attendance_status);
    }
}
