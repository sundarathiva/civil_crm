<?php

namespace App\Http\Controllers;

use App\Models\Bridge;
use App\Models\DailyReport;
use App\Models\Equipment;
use App\Models\EquipmentUsage;
use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\Pillar;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Models\Wall;
use App\Models\Worker;
use App\Models\WorkerAttendance;
use App\Support\Options;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', [
            'groups' => [
                'Projects' => [
                    'projects' => 'Project list',
                    'project-status' => 'Project status',
                    'project-progress' => 'Project progress',
                    'completed-projects' => 'Completed projects',
                    'active-projects' => 'In-progress projects',
                ],
                'Locations' => [
                    'locations' => 'Location-wise progress',
                    'location-reports' => 'Location-wise daily reports',
                    'location-work' => 'Location-wise work status',
                ],
                'Materials' => [
                    'stock' => 'Current stock',
                    'material-received' => 'Material received',
                    'material-issued' => 'Material issued',
                    'material-used' => 'Material used',
                    'low-stock' => 'Low stock',
                ],
                'Equipment' => [
                    'equipment' => 'Equipment list',
                    'equipment-usage' => 'Equipment usage',
                    'equipment-project' => 'Equipment by project',
                    'equipment-maintenance' => 'Equipment maintenance',
                ],
                'Workers' => [
                    'workers' => 'Worker list',
                    'attendance' => 'Worker attendance',
                    'workers-project' => 'Workers by project',
                    'workers-location' => 'Workers by location',
                ],
                'Daily reports' => [
                    'daily-reports' => 'Daily site reports',
                    'daily-by-project' => 'Project-wise daily reports',
                    'daily-by-location' => 'Location-wise daily reports',
                    'daily-by-date' => 'Date-wise daily reports',
                ],
            ],
        ]);
    }

    public function show(Request $request, string $report)
    {
        $user = auth()->user();
        $projectIds = Project::query()->visibleTo($user)->pluck('id');
        [$title, $columns, $rows] = match ($report) {
            'projects', 'project-status' => $this->projects($projectIds, null),
            'project-progress' => $this->projects($projectIds, null, true),
            'completed-projects' => $this->projects($projectIds, 'completed'),
            'active-projects' => $this->projects($projectIds, 'active'),
            'locations' => $this->locations($projectIds),
            'location-reports' => $this->locationReports($projectIds),
            'location-work' => $this->locationWork($projectIds),
            'stock' => $this->stock(false),
            'low-stock' => $this->stock(true),
            'material-received' => $this->movements('received'),
            'material-issued' => $this->movements('issued'),
            'material-used' => $this->movements('used'),
            'equipment' => $this->equipment($projectIds, null),
            'equipment-project' => $this->equipment($projectIds, 'project'),
            'equipment-maintenance' => $this->equipment($projectIds, 'maintenance'),
            'equipment-usage' => $this->usage($projectIds),
            'workers' => $this->workers($projectIds, 'list'),
            'workers-project' => $this->workers($projectIds, 'project'),
            'workers-location' => $this->workers($projectIds, 'location'),
            'attendance' => $this->attendance($projectIds, $request),
            'daily-reports', 'daily-by-project', 'daily-by-location', 'daily-by-date' => $this->daily($projectIds, $report, $request),
            default => abort(404),
        };

        return view('reports.show', compact('title', 'columns', 'rows', 'report'));
    }

    private function projects($ids, ?string $filter, bool $breakdown = false): array
    {
        $query = Project::with('type')->whereIn('id', $ids)->orderBy('name');
        if ($filter === 'completed') {
            $query->where('status', 'completed');
        }
        if ($filter === 'active') {
            $query->whereIn('status', ['started', 'in_progress']);
        }

        $rows = $query->get()->map(function (Project $project) use ($breakdown) {
            $row = [
                $project->code,
                $project->name,
                $project->type?->name,
                $project->client_name,
                $project->statusLabel(),
                $project->progress.'%',
            ];
            if ($breakdown) {
                $row[] = round((float) ($project->pillars()->avg('progress') ?? 0), 1).'%';
                $row[] = round((float) ($project->walls()->avg('progress') ?? 0), 1).'%';
                $row[] = round((float) ($project->bridges()->avg('progress') ?? 0), 1).'%';
            }

            return $row;
        });

        $columns = ['Code', 'Project', 'Type', 'Client', 'Status', 'Overall'];
        if ($breakdown) {
            $columns = array_merge($columns, ['Pillars', 'Walls', 'Bridges']);
        }

        return ['Project report', $columns, $rows];
    }

    private function locations($ids): array
    {
        $rows = ProjectLocation::with('project')->whereIn('project_id', $ids)->orderBy('name')->get()->map(function (ProjectLocation $location) {
            $pillar = round((float) ($location->pillars()->avg('progress') ?? 0), 1);
            $wall = round((float) ($location->walls()->avg('progress') ?? 0), 1);
            $bridge = round((float) ($location->bridges()->avg('progress') ?? 0), 1);

            return [$location->project->code, $location->name, $location->chainage ?: '—', $location->statusLabel(), $pillar.'%', $wall.'%', $bridge.'%'];
        });

        return ['Location-wise progress', ['Project', 'Location', 'Chainage', 'Status', 'Pillars', 'Walls', 'Bridges'], $rows];
    }

    private function locationReports($ids): array
    {
        $rows = ProjectLocation::with('project')->whereIn('project_id', $ids)->orderBy('name')->get()->map(function (ProjectLocation $location) {
            $count = DailyReport::where('project_location_id', $location->id)->count();
            $latest = DailyReport::where('project_location_id', $location->id)->latest('report_date')->first();

            return [$location->project->name, $location->name, $count, $latest?->report_date?->format('d M Y') ?: '—', $latest?->statusLabel() ?: '—'];
        });

        return ['Location-wise daily reports', ['Project', 'Location', 'Reports', 'Latest date', 'Latest status'], $rows];
    }

    private function locationWork($ids): array
    {
        $rows = collect();
        Pillar::with(['project', 'location'])->whereIn('project_id', $ids)->orderBy('code')->get()->each(function (Pillar $item) use ($rows) {
            $rows->push([$item->project->name, $item->location->name, 'Pillar', $item->code, $item->statusLabel(), $item->progress.'%']);
        });
        Wall::with(['project', 'location'])->whereIn('project_id', $ids)->orderBy('code')->get()->each(function (Wall $item) use ($rows) {
            $rows->push([$item->project->name, $item->location->name, 'Wall', $item->code, $item->statusLabel(), $item->progress.'%']);
        });
        Bridge::with(['project', 'location'])->whereIn('project_id', $ids)->orderBy('code')->get()->each(function (Bridge $item) use ($rows) {
            $rows->push([$item->project->name, $item->location->name, 'Bridge', $item->code, $item->statusLabel(), $item->progress.'%']);
        });

        return ['Location-wise work status', ['Project', 'Location', 'Work', 'Code', 'Status', 'Progress'], $rows];
    }

    private function stock(bool $lowOnly): array
    {
        $rows = Material::with('stock')->orderBy('name')
            ->when($lowOnly, fn ($query) => $query->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->get()
            ->map(fn (Material $material) => [
                $material->code,
                $material->name,
                $material->category,
                $material->unit,
                $material->stock?->opening_qty ?? '0.00',
                $material->stock?->received_qty ?? '0.00',
                $material->stock?->issued_qty ?? '0.00',
                $material->stock?->used_qty ?? '0.00',
                $material->stock?->returned_qty ?? '0.00',
                $material->current_stock,
                $material->minimum_stock,
                $material->isLow() ? 'Low' : 'OK',
            ]);

        return [$lowOnly ? 'Low stock' : 'Current stock', ['Code', 'Material', 'Category', 'Unit', 'Opening', 'Received', 'Issued', 'Used', 'Returned', 'Current', 'Minimum', 'Alert'], $rows];
    }

    private function movements(string $type): array
    {
        $rows = MaterialTransaction::with(['material', 'project', 'user'])
            ->where('type', $type)
            ->latest('transacted_on')
            ->get()
            ->map(fn (MaterialTransaction $txn) => [
                $txn->transacted_on->format('d M Y'),
                $txn->material->name,
                $txn->quantity.' '.$txn->material->unit,
                $txn->project?->name ?: 'Store',
                $txn->user?->name ?: '—',
                $txn->remarks ?: '—',
            ]);

        return [Options::label(Options::STOCK_TYPES, $type), ['Date', 'Material', 'Quantity', 'Project', 'Recorded by', 'Remarks'], $rows];
    }

    private function equipment($ids, ?string $mode): array
    {
        $query = Equipment::with(['project', 'location'])->orderBy('name');
        if ($mode === 'maintenance') {
            $query->where('status', 'maintenance');
        }
        if (! auth()->user()->hasRole('super_admin')) {
            $query->where(function ($inner) use ($ids) {
                $inner->whereIn('project_id', $ids)->orWhereNull('project_id');
            });
        }

        $rows = $query->get()->map(fn (Equipment $item) => [
            $item->code,
            $item->name,
            $item->equipment_type,
            $item->registration_number ?: '—',
            $item->project?->name ?: 'Unassigned',
            $item->location?->name ?: '—',
            $item->operator_name ?: '—',
            $item->statusLabel(),
        ]);

        $title = match ($mode) {
            'maintenance' => 'Equipment under maintenance',
            'project' => 'Equipment by project',
            default => 'Equipment list',
        };

        return [$title, ['Code', 'Name', 'Type', 'Registration', 'Project', 'Location', 'Operator', 'Status'], $rows];
    }

    private function usage($ids): array
    {
        $rows = EquipmentUsage::with(['equipment', 'project', 'location'])
            ->whereIn('project_id', $ids)
            ->latest('used_on')
            ->get()
            ->map(fn (EquipmentUsage $usage) => [
                $usage->used_on->format('d M Y'),
                $usage->equipment->code,
                $usage->project->name,
                $usage->location?->name ?: '—',
                $usage->working_hours ? $usage->working_hours.' h' : '—',
                $usage->fuel_used ? $usage->fuel_used.' '.$usage->fuel_unit : '—',
                $usage->work_description ?: '—',
            ]);

        return ['Equipment usage', ['Date', 'Equipment', 'Project', 'Location', 'Hours', 'Fuel', 'Work'], $rows];
    }

    private function workers($ids, string $mode): array
    {
        $rows = Worker::with(['project', 'location'])
            ->when(! auth()->user()->hasRole('super_admin'), fn ($query) => $query->whereIn('project_id', $ids))
            ->orderBy('name')
            ->get()
            ->map(fn (Worker $worker) => match ($mode) {
                'project' => [$worker->project?->name ?: 'Unassigned', $worker->code, $worker->name, $worker->worker_type, $worker->skill ?: '—'],
                'location' => [$worker->location?->name ?: 'Unassigned', $worker->project?->name ?: '—', $worker->code, $worker->name, $worker->worker_type],
                default => [$worker->code, $worker->name, $worker->mobile, $worker->worker_type, $worker->skill ?: '—', $worker->project?->name ?: '—', number_format((float) $worker->daily_wage, 2)],
            });

        $columns = match ($mode) {
            'project' => ['Project', 'Code', 'Worker', 'Type', 'Skill'],
            'location' => ['Location', 'Project', 'Code', 'Worker', 'Type'],
            default => ['Code', 'Name', 'Mobile', 'Type', 'Skill', 'Project', 'Daily wage'],
        };

        return ['Worker report', $columns, $rows];
    }

    private function attendance($ids, Request $request): array
    {
        $rows = WorkerAttendance::with(['worker', 'project', 'location'])
            ->whereIn('project_id', $ids)
            ->when($request->date, fn ($query, $date) => $query->whereDate('attended_on', $date))
            ->latest('attended_on')
            ->limit(300)
            ->get()
            ->map(fn (WorkerAttendance $row) => [
                $row->attended_on->format('d M Y'),
                $row->worker->name,
                $row->project?->name ?: '—',
                $row->location?->name ?: '—',
                $row->statusLabel(),
                $row->overtime_hours > 0 ? $row->overtime_hours.' h' : '—',
            ]);

        return ['Worker attendance', ['Date', 'Worker', 'Project', 'Location', 'Status', 'Overtime'], $rows];
    }

    private function daily($ids, string $report, Request $request): array
    {
        $rows = DailyReport::with(['project', 'location', 'siteEngineer'])
            ->whereIn('project_id', $ids)
            ->when($request->project_id, fn ($query, $id) => $query->where('project_id', $id))
            ->when($request->date, fn ($query, $date) => $query->whereDate('report_date', $date))
            ->latest('report_date')
            ->get()
            ->map(fn (DailyReport $item) => [
                $item->code,
                $item->report_date->format('d M Y'),
                $item->project->name,
                $item->location->name,
                $item->workLabel(),
                $item->progress.'%',
                $item->worker_count,
                $item->siteEngineer?->name ?: '—',
                $item->statusLabel(),
            ]);

        $title = match ($report) {
            'daily-by-project' => 'Project-wise daily reports',
            'daily-by-location' => 'Location-wise daily reports',
            'daily-by-date' => 'Date-wise daily reports',
            default => 'Daily site reports',
        };

        return [$title, ['Report', 'Date', 'Project', 'Location', 'Work', 'Progress', 'Workers', 'Site engineer', 'Status'], $rows];
    }
}
