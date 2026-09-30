<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id', 'recorded_on', 'pillar_progress', 'wall_progress',
    'bridge_progress', 'overall_progress',
])]
class ProjectProgress extends Model
{
    protected $table = 'project_progress';

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'pillar_progress' => 'decimal:1',
            'wall_progress' => 'decimal:1',
            'bridge_progress' => 'decimal:1',
            'overall_progress' => 'decimal:1',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
