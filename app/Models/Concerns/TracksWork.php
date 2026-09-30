<?php

namespace App\Models\Concerns;

use App\Models\Project;
use App\Models\ProjectLocation;
use App\Support\Options;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TracksWork
{
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProjectLocation::class, 'project_location_id');
    }

    public function statusLabel(): string
    {
        return Options::label(Options::WORK_STATUSES, $this->status);
    }
}
