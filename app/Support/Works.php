<?php

namespace App\Support;

use App\Models\Bridge;
use App\Models\Pillar;
use App\Models\Project;
use App\Models\User;
use App\Models\Wall;
use Illuminate\Database\Eloquent\Model;

class Works
{
    public static function routeTypes(): array
    {
        return [
            'pillars' => ['singular' => 'pillar', 'label' => 'Pillar', 'plural' => 'Pillars', 'model' => Pillar::class],
            'walls' => ['singular' => 'wall', 'label' => 'Wall', 'plural' => 'Walls', 'model' => Wall::class],
            'bridges' => ['singular' => 'bridge', 'label' => 'Bridge', 'plural' => 'Bridges', 'model' => Bridge::class],
        ];
    }

    public static function meta(string $type): array
    {
        return self::routeTypes()[$type] ?? abort(404);
    }

    public static function find(string $singular, ?int $id): ?Model
    {
        if (! $id) {
            return null;
        }

        return match ($singular) {
            'pillar' => Pillar::find($id),
            'wall' => Wall::find($id),
            'bridge' => Bridge::find($id),
            default => null,
        };
    }

    public static function forProject(Project $project): array
    {
        return [
            'pillar' => $project->pillars()->orderBy('code')->get(),
            'wall' => $project->walls()->orderBy('code')->get(),
            'bridge' => $project->bridges()->orderBy('code')->get(),
        ];
    }

    public static function canReach(User $user, Model $work): bool
    {
        $project = $work->project;

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('engineer')) {
            return (int) $project->engineer_id === (int) $user->id;
        }

        if ($user->hasRole('site_engineer')) {
            return (int) $project->site_engineer_id === (int) $user->id
                || (int) $work->location?->site_engineer_id === (int) $user->id;
        }

        return false;
    }
}
