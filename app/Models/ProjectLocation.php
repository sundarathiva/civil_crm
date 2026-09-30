<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'project_id', 'name', 'address', 'chainage', 'latitude', 'longitude',
    'site_engineer_id', 'description', 'status',
])]
class ProjectLocation extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function siteEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_engineer_id');
    }

    public function pillars(): HasMany
    {
        return $this->hasMany(Pillar::class);
    }

    public function walls(): HasMany
    {
        return $this->hasMany(Wall::class);
    }

    public function bridges(): HasMany
    {
        return $this->hasMany(Bridge::class);
    }

    public function statusLabel(): string
    {
        return Options::label(Options::LOCATION_STATUSES, $this->status);
    }
}
