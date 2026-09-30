<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\DailyReportPhoto;
use App\Models\Equipment;
use App\Models\EquipmentUsage;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Models\Worker;
use App\Models\WorkerAttendance;
use App\Services\ProgressService;
use App\Services\StockService;
use App\Support\Access;
use App\Support\Options;
use App\Support\Works;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::query()->visibleTo(auth()->user())->orderBy('name')->get();
        $reports = DailyReport::with(['project', 'location', 'siteEngineer'])
            ->whereIn('project_id', $projects->pluck('id'))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->project_id, fn ($query, $id) => $query->where('project_id', $id))
            ->when($request->date, fn ($query, $date) => $query->whereDate('report_date', $date))
            ->latest('report_date')
            ->paginate(12)
            ->withQueryString();

        return view('daily-reports.index', [
            'reports' => $reports,
            'projects' => $projects,
            'statuses' => Options::REPORT_STATUSES,
            'canCreate' => auth()->user()->hasRole('super_admin', 'site_engineer', 'engineer'),
        ]);
    }

    public function create(Request $request)
    {
        return view('daily-reports.form', $this->formData(new DailyReport([
            'project_id' => $request->integer('project_id') ?: null,
            'project_location_id' => $request->integer('project_location_id') ?: null,
            'report_date' => now()->toDateString(),
            'status' => 'draft',
            'progress' => 0,
            'worker_count' => 0,
        ])));
    }

    public function store(Request $request)
    {
        $report = DB::transaction(function () use ($request) {
            $data = $this->payload($request);
            $report = DailyReport::create($data + [
                'code' => $this->nextCode(),
                'site_engineer_id' => auth()->user()->hasRole('site_engineer') ? auth()->id() : ($data['site_engineer_id'] ?? auth()->id()),
            ]);
            $this->syncLines($report, $request);
            $this->storePhotos($report, $request);

            return $report;
        });

        return redirect()->route('daily-reports.show', $report)->with('status', $report->status === 'submitted'
            ? 'Daily site report submitted for review.'
            : 'Draft saved.');
    }

    public function show(DailyReport $dailyReport)
    {
        $this->authorizeView($dailyReport);
        $dailyReport->load([
            'project', 'location', 'siteEngineer', 'reviewer',
            'materials.material', 'equipmentLines.equipment', 'workers.worker', 'photos',
        ]);

        return view('daily-reports.show', [
            'report' => $dailyReport,
            'canReview' => Access::reviewReports(auth()->user(), $dailyReport->project),
            'canEdit' => $dailyReport->isEditable() && Access::submitReport(auth()->user(), $dailyReport->project),
        ]);
    }

    public function edit(DailyReport $dailyReport)
    {
        $this->authorizeView($dailyReport);
        abort_unless($dailyReport->isEditable() && Access::submitReport(auth()->user(), $dailyReport->project), 403);
        $dailyReport->load(['materials', 'equipmentLines', 'workers', 'photos']);

        return view('daily-reports.form', $this->formData($dailyReport));
    }

    public function update(Request $request, DailyReport $dailyReport)
    {
        $this->authorizeView($dailyReport);
        abort_unless($dailyReport->isEditable() && Access::submitReport(auth()->user(), $dailyReport->project), 403);
        abort_if($dailyReport->materials()->where('posted', true)->exists(), 422, 'Posted material usage cannot be edited.');

        DB::transaction(function () use ($request, $dailyReport) {
            $data = $this->payload($request, $dailyReport);
            $dailyReport->update($data);
            $this->syncLines($dailyReport, $request);
            $this->storePhotos($dailyReport, $request);
            if ($request->filled('remove_photos')) {
                $photos = DailyReportPhoto::where('daily_report_id', $dailyReport->id)
                    ->whereIn('id', $request->input('remove_photos'))
                    ->get();
                foreach ($photos as $photo) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->path);
                    $photo->delete();
                }
            }
        });

        return redirect()->route('daily-reports.show', $dailyReport)->with('status', 'Daily site report updated.');
    }

    public function review(Request $request, DailyReport $dailyReport, StockService $stock, ProgressService $progress)
    {
        abort_unless(Access::reviewReports(auth()->user(), $dailyReport->project), 403);
        abort_unless($dailyReport->status === 'submitted', 422);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['nullable', 'string', 'max:1000', 'required_if:decision,rejected'],
        ]);

        if ($data['decision'] === 'rejected') {
            $dailyReport->update([
                'status' => 'rejected',
                'review_note' => $data['review_note'],
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return back()->with('status', 'Report rejected. The site engineer can correct and resubmit it.');
        }

        DB::transaction(function () use ($dailyReport, $data, $stock, $progress) {
            $dailyReport->load('materials.material');
            foreach ($dailyReport->materials as $line) {
                if ($line->posted) {
                    continue;
                }
                $stock->apply(
                    $line->material,
                    'used',
                    (float) $line->quantity,
                    $dailyReport->report_date->toDateString(),
                    $dailyReport->project_id,
                    $dailyReport->project_location_id,
                    'Used on '.$dailyReport->code,
                    auth()->user(),
                    $dailyReport,
                );
                $line->update(['posted' => true]);
            }

            $work = Works::find($dailyReport->work_type, $dailyReport->work_id);
            if ($work) {
                $work->update([
                    'progress' => $dailyReport->progress,
                    'status' => (float) $dailyReport->progress >= 100 ? 'completed' : 'in_progress',
                ]);
            }

            $dailyReport->update([
                'status' => 'approved',
                'review_note' => $data['review_note'] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
            $progress->recalculate($dailyReport->project);
        });

        return back()->with('status', 'Report approved. Stock and project progress were updated.');
    }

    private function formData(DailyReport $report): array
    {
        $projects = Project::query()->visibleTo(auth()->user())->with(['locations', 'pillars', 'walls', 'bridges'])->orderBy('name')->get()
            ->filter(fn (Project $project) => Access::submitReport(auth()->user(), $project));

        return [
            'report' => $report,
            'projects' => $projects,
            'materials' => Material::where('status', 'active')->orderBy('name')->get(),
            'equipment' => Equipment::orderBy('name')->get(),
            'workers' => Worker::where('status', 'active')->orderBy('name')->get(),
            'workTypes' => Options::WORK_TYPES,
            'attendance' => Options::ATTENDANCE,
        ];
    }

    private function payload(Request $request, ?DailyReport $report = null): array
    {
        $request->merge([
            'materials' => $this->filledRows($request->input('materials', [])),
            'equipment_lines' => $this->filledRows($request->input('equipment_lines', []), 'equipment_id'),
            'worker_lines' => $this->filledRows($request->input('worker_lines', []), 'worker_id'),
        ]);

        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'project_location_id' => ['required', 'exists:project_locations,id'],
            'report_date' => ['required', 'date'],
            'work_type' => ['nullable', Rule::in(array_keys(Options::WORK_TYPES))],
            'work_id' => ['nullable', 'integer'],
            'work_description' => ['required', 'string'],
            'progress' => ['required', 'numeric', 'min:0', 'max:100'],
            'worker_count' => ['required', 'integer', 'min:0'],
            'issues' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'materials' => ['nullable', 'array'],
            'materials.*.material_id' => ['required', 'exists:materials,id'],
            'materials.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'equipment_lines' => ['nullable', 'array'],
            'equipment_lines.*.equipment_id' => ['required', 'exists:equipment,id'],
            'equipment_lines.*.working_hours' => ['nullable', 'numeric', 'min:0'],
            'equipment_lines.*.fuel_used' => ['nullable', 'numeric', 'min:0'],
            'equipment_lines.*.remarks' => ['nullable', 'string', 'max:255'],
            'worker_lines' => ['nullable', 'array'],
            'worker_lines.*.worker_id' => ['required', 'exists:workers,id'],
            'worker_lines.*.attendance_status' => ['required', Rule::in(array_keys(Options::ATTENDANCE))],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        abort_unless(Access::submitReport(auth()->user(), $project), 403);
        $location = ProjectLocation::findOrFail($data['project_location_id']);
        if ((int) $location->project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'project_location_id' => 'Choose a location that belongs to this project.',
            ]);
        }

        if (! empty($data['work_type']) && $data['work_type'] !== 'other' && ! empty($data['work_id'])) {
            $work = Works::find($data['work_type'], (int) $data['work_id']);
            if (! $work || (int) $work->project_id !== (int) $project->id) {
                throw ValidationException::withMessages(['work_id' => 'Choose work from this project.']);
            }
        }

        if ($data['work_type'] === 'other') {
            $data['work_id'] = null;
        }

        $data['status'] = $data['action'] === 'submit' ? 'submitted' : 'draft';
        unset($data['action'], $data['materials'], $data['equipment_lines'], $data['worker_lines'], $data['photos']);

        if ($report && $report->status === 'rejected' && $data['status'] === 'submitted') {
            $data['review_note'] = $report->review_note;
        }

        return $data;
    }

    private function syncLines(DailyReport $report, Request $request): void
    {
        $report->materials()->delete();
        $report->equipmentLines()->delete();
        $report->workers()->delete();
        EquipmentUsage::where('daily_report_id', $report->id)->delete();

        foreach ($request->input('materials', []) as $row) {
            $report->materials()->create([
                'material_id' => $row['material_id'],
                'quantity' => $row['quantity'],
                'posted' => false,
            ]);
        }

        foreach ($request->input('equipment_lines', []) as $row) {
            $report->equipmentLines()->create([
                'equipment_id' => $row['equipment_id'],
                'working_hours' => $row['working_hours'] ?? null,
                'fuel_used' => $row['fuel_used'] ?? null,
                'remarks' => $row['remarks'] ?? null,
            ]);

            if ($report->status === 'submitted') {
                EquipmentUsage::create([
                    'equipment_id' => $row['equipment_id'],
                    'project_id' => $report->project_id,
                    'project_location_id' => $report->project_location_id,
                    'daily_report_id' => $report->id,
                    'used_on' => $report->report_date->toDateString(),
                    'working_hours' => $row['working_hours'] ?? null,
                    'fuel_used' => $row['fuel_used'] ?? null,
                    'work_description' => $report->work_description,
                    'remarks' => $row['remarks'] ?? null,
                    'user_id' => auth()->id(),
                ]);
            }
        }

        foreach ($request->input('worker_lines', []) as $row) {
            $report->workers()->create([
                'worker_id' => $row['worker_id'],
                'attendance_status' => $row['attendance_status'],
            ]);

            if ($report->status === 'submitted') {
                WorkerAttendance::updateOrCreate(
                    ['worker_id' => $row['worker_id'], 'attended_on' => $report->report_date->toDateString()],
                    [
                        'project_id' => $report->project_id,
                        'project_location_id' => $report->project_location_id,
                        'status' => $row['attendance_status'],
                        'overtime_hours' => $row['attendance_status'] === 'overtime' ? 2 : 0,
                        'marked_by' => auth()->id(),
                    ]
                );
            }
        }
    }

    private function storePhotos(DailyReport $report, Request $request): void
    {
        foreach ($request->file('photos', []) as $file) {
            $path = $file->store('daily-reports/'.$report->id, 'public');
            $report->photos()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
            ]);
        }
    }

    private function filledRows(array $rows, string $key = 'material_id'): array
    {
        return array_values(array_filter($rows, fn ($row) => is_array($row) && ! empty($row[$key])));
    }

    private function nextCode(): string
    {
        $next = DailyReport::count() + 1;

        return 'DSR-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function authorizeView(DailyReport $report): void
    {
        abort_unless(Project::query()->visibleTo(auth()->user())->whereKey($report->project_id)->exists(), 403);
    }
}
