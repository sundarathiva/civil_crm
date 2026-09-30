<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectProgress;

class ProgressService
{
    public function recalculate(Project $project): void
    {
        $hasPillars = $project->pillars()->exists();
        $hasWalls = $project->walls()->exists();
        $hasBridges = $project->bridges()->exists();

        $pillar = $hasPillars ? round((float) $project->pillars()->avg('progress'), 1) : 0;
        $wall = $hasWalls ? round((float) $project->walls()->avg('progress'), 1) : 0;
        $bridge = $hasBridges ? round((float) $project->bridges()->avg('progress'), 1) : 0;

        $weights = [];
        if ($hasPillars) {
            $weights['pillar'] = 50;
        }
        if ($hasWalls) {
            $weights['wall'] = 30;
        }
        if ($hasBridges) {
            $weights['bridge'] = 20;
        }

        $overall = 0.0;
        $sum = array_sum($weights);
        if ($sum > 0) {
            $values = compact('pillar', 'wall', 'bridge');
            foreach ($weights as $key => $weight) {
                $overall += $values[$key] * ($weight / $sum);
            }
        }

        $overall = round($overall, 1);
        $project->update(['progress' => $overall]);

        ProjectProgress::updateOrCreate(
            ['project_id' => $project->id, 'recorded_on' => now()->toDateString()],
            [
                'pillar_progress' => $pillar,
                'wall_progress' => $wall,
                'bridge_progress' => $bridge,
                'overall_progress' => $overall,
            ]
        );
    }
}
