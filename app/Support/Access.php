<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;

class Access
{
    public static function super(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public static function createProject(User $user): bool
    {
        return $user->hasRole('super_admin', 'engineer');
    }

    public static function editProject(User $user, Project $project): bool
    {
        return self::super($user)
            || ($user->hasRole('engineer') && (int) $project->engineer_id === (int) $user->id);
    }

    public static function reviewReports(User $user, Project $project): bool
    {
        return self::editProject($user, $project);
    }

    public static function submitReport(User $user, Project $project): bool
    {
        if (self::super($user) || self::editProject($user, $project)) {
            return true;
        }

        if (! $user->hasRole('site_engineer')) {
            return false;
        }

        if ((int) $project->site_engineer_id === (int) $user->id) {
            return true;
        }

        return $project->locations()->where('site_engineer_id', $user->id)->exists();
    }

    public static function manageMasters(User $user): bool
    {
        return self::super($user);
    }

    public static function assignPeople(User $user, Project $project): bool
    {
        return self::editProject($user, $project);
    }

    public static function markAttendance(User $user): bool
    {
        return $user->hasRole('super_admin', 'engineer', 'site_engineer');
    }
}
