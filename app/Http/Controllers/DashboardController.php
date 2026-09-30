<?php

namespace App\Http\Controllers;

use App\Models\Bridge;
use App\Models\DailyReport;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Pillar;
use App\Models\Project;
use App\Models\Wall;
use App\Models\Worker;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();

        if ($user->hasRole('worker')) {
            return app(MyWorkController::class)->index();
        }

        $projects = fn () => Project::query()->visibleTo($user);
        $ids = $projects()->pluck('id');

        $stats = [
            'projects' => $projects()->count(),
            'active' => $projects()->whereIn('status', ['started', 'in_progress'])->count(),
            'completed' => $projects()->where('status', 'completed')->count(),
            'progress' => (int) round((float) ($projects()->where('status', '!=', 'new')->avg('progress') ?? 0)),
            'pillars' => Pillar::whereIn('project_id', $ids)->count(),
            'wall_length' => (float) Wall::whereIn('project_id', $ids)->sum('length'),
            'bridges' => Bridge::whereIn('project_id', $ids)->count(),
            'workers' => Worker::where('status', 'active')->whereIn('project_id', $ids)->count(),
            'pending_reports' => DailyReport::where('status', 'submitted')->whereIn('project_id', $ids)->count(),
            'low_stock' => Material::whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            'maintenance' => Equipment::where('status', 'maintenance')
                ->when(! $user->hasRole('super_admin'), fn ($query) => $query->whereIn('project_id', $ids))
                ->count(),
        ];

        $portfolio = $projects()->with('type')->orderByDesc('updated_at')->take(6)->get();
        foreach ($portfolio as $project) {
            $project->pillar_progress = round((float) ($project->pillars()->avg('progress') ?? 0), 1);
            $project->wall_progress = round((float) ($project->walls()->avg('progress') ?? 0), 1);
            $project->bridge_progress = round((float) ($project->bridges()->avg('progress') ?? 0), 1);
        }

        $queue = DailyReport::with(['project', 'location', 'siteEngineer'])
            ->whereIn('project_id', $ids)
            ->where('status', 'submitted')
            ->latest('report_date')
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'portfolio', 'queue'));
    }
}
