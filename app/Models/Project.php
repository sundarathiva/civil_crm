<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'name', 'project_type_id', 'client_name', 'location_summary',
    'start_date', 'expected_completion_date', 'engineer_id', 'site_engineer_id',
    'description', 'status', 'progress',
])]
class Project extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_completion_date' => 'date',
            'progress' => 'decimal:1',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }

    public function siteEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_engineer_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ProjectLocation::class);
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

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ProjectStatusHistory::class);
    }

    public function progressEntries(): HasMany
    {
        return $this->hasMany(ProjectProgress::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }

    public function statusLabel(): string
    {
        return Options::label(Options::PROJECT_STATUSES, $this->status);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if ($user->hasRole('engineer')) {
            return $query->where('engineer_id', $user->id);
        }

        if ($user->hasRole('site_engineer')) {
            return $query->where(function (Builder $inner) use ($user) {
                $inner->where('site_engineer_id', $user->id)
                    ->orWhereHas('locations', fn ($locations) => $locations->where('site_engineer_id', $user->id));
            });
        }

        if ($user->hasRole('worker') && $user->worker) {
            return $query->whereIn('id', $user->worker->assignments()->pluck('project_id'));
        }

        return $query->whereRaw('0 = 1');
    }
}
