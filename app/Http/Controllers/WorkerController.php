<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectLocation;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerAssignment;
use App\Models\WorkerAttendance;
use App\Support\Access;
use App\Support\Options;
use App\Support\Works;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkerController extends Controller
{
    public function index(Request $request)
    {
        
        $projects = Project::query()->visibleTo(auth()->user())->orderBy('name')->get();
        $workers = Worker::with(['project', 'location', 'user'])
            ->when(! auth()->user()->hasRole('super_admin'), fn ($query) => $query->whereIn('project_id', $projects->pluck('id')))
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when($request->worker_type, fn ($query, $type) => $query->where('worker_type', $type))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('workers.index', [
            'workers' => $workers,
            'types' => Options::WORKER_TYPES,
            'canManage' => Access::manageMasters(auth()->user()),
        ]);
    }

    public function create()
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);

        return view('workers.form', $this->formData(new Worker(['status' => 'active'])));
    }

    public function store(Request $request)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);
        $worker = Worker::create($this->validateWorker($request));

        return redirect()->route('workers.show', $worker)->with('status', 'Worker added.');
    }

    public function show(Worker $worker)
    {
        $this->authorizeView($worker);
        $worker->load(['project', 'location', 'user', 'assignments.project', 'assignments.location', 'attendance' => fn ($q) => $q->latest('attended_on')->limit(10)]);

        return view('workers.show', [
            'worker' => $worker,
            'projects' => Project::query()->visibleTo(auth()->user())->with(['locations', 'pillars', 'walls', 'bridges'])->orderBy('name')->get(),
            'canAssign' => auth()->user()->hasRole('super_admin', 'engineer'),
            'workTypes' => Options::WORK_TYPES,
        ]);
    }

    public function edit(Worker $worker)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);

        return view('workers.form', $this->formData($worker));
    }

    public function update(Request $request, Worker $worker)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);
        $worker->update($this->validateWorker($request, $worker));

        return redirect()->route('workers.show', $worker)->with('status', 'Worker updated.');
    }

    public function destroy(Worker $worker)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);
        $worker->delete();

        return redirect()->route('workers.index')->with('status', 'Worker removed.');
    }

    public function assign(Request $request, Worker $worker)
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'project_location_id' => ['nullable', 'exists:project_locations,id'],
            'work_type' => ['nullable', Rule::in(['pillar', 'wall', 'bridge'])],
            'work_id' => ['nullable', 'integer'],
            'assigned_on' => ['required', 'date'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        abort_unless(Access::assignPeople(auth()->user(), $project), 403);

        if (! empty($data['work_type']) && ! empty($data['work_id'])) {
            $work = Works::find($data['work_type'], (int) $data['work_id']);
            abort_unless($work && (int) $work->project_id === (int) $project->id, 422);
        }

        WorkerAssignment::create($data + ['worker_id' => $worker->id, 'status' => 'active']);
        $worker->update([
            'project_id' => $project->id,
            'project_location_id' => $data['project_location_id'] ?? $worker->project_location_id,
        ]);

        return back()->with('status', 'Worker assigned.');
    }

    public function attendance(Request $request)
    {
        abort_unless(Access::markAttendance(auth()->user()), 403);
        $projects = Project::query()->visibleTo(auth()->user())->with('locations')->orderBy('name')->get();
        $date = $request->date('date')?->toDateString() ?? now()->toDateString();
        $projectId = $request->integer('project_id');
        $locationId = $request->integer('project_location_id');
        $workers = collect();
        $existing = collect();

        if ($projectId) {
            $project = Project::findOrFail($projectId);
            abort_unless(Project::query()->visibleTo(auth()->user())->whereKey($project->id)->exists(), 403);
            $workers = Worker::query()
                ->where('status', 'active')
                ->where(function ($query) use ($projectId, $locationId) {
                    $query->where('project_id', $projectId)
                        ->orWhereHas('assignments', function ($assignments) use ($projectId, $locationId) {
                            $assignments->where('project_id', $projectId)->where('status', 'active');
                            if ($locationId) {
                                $assignments->where('project_location_id', $locationId);
                            }
                        });
                })
                ->when($locationId, fn ($query) => $query->where(function ($inner) use ($locationId) {
                    $inner->where('project_location_id', $locationId)->orWhereHas('assignments', fn ($a) => $a->where('project_location_id', $locationId));
                }))
                ->orderBy('name')
                ->get();
            $existing = WorkerAttendance::whereDate('attended_on', $date)
                ->whereIn('worker_id', $workers->pluck('id'))
                ->get()
                ->keyBy('worker_id');
        }

        return view('workers.attendance', [
            'projects' => $projects,
            'date' => $date,
            'projectId' => $projectId,
            'locationId' => $locationId,
            'workers' => $workers,
            'existing' => $existing,
            'statuses' => Options::ATTENDANCE,
        ]);
    }

    public function storeAttendance(Request $request)
    {
        abort_unless(Access::markAttendance(auth()->user()), 403);
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'project_location_id' => ['nullable', 'exists:project_locations,id'],
            'attended_on' => ['required', 'date'],
            'rows' => ['required', 'array'],
            'rows.*.worker_id' => ['required', 'exists:workers,id'],
            'rows.*.status' => ['required', Rule::in(array_keys(Options::ATTENDANCE))],
            'rows.*.overtime_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        abort_unless(
            Access::editProject(auth()->user(), $project) || Access::submitReport(auth()->user(), $project),
            403
        );

        foreach ($data['rows'] as $row) {
            WorkerAttendance::updateOrCreate(
                ['worker_id' => $row['worker_id'], 'attended_on' => $data['attended_on']],
                [
                    'project_id' => $project->id,
                    'project_location_id' => $data['project_location_id'] ?? null,
                    'status' => $row['status'],
                    'overtime_hours' => $row['status'] === 'overtime' ? ($row['overtime_hours'] ?? 0) : 0,
                    'marked_by' => auth()->id(),
                ]
            );
        }

        return redirect()->route('workers.attendance', [
            'project_id' => $project->id,
            'project_location_id' => $data['project_location_id'] ?? null,
            'date' => $data['attended_on'],
        ])->with('status', 'Attendance saved.');
    }

    private function formData(Worker $worker): array
    {
        $linked = Worker::whereNotNull('user_id')->when($worker->exists, fn ($q) => $q->whereKeyNot($worker->id))->pluck('user_id');

        return [
            'worker' => $worker,
            'types' => Options::WORKER_TYPES,
            'projects' => Project::orderBy('name')->get(),
            'locations' => ProjectLocation::orderBy('name')->get(),
            'accounts' => User::withRole('worker')->whereNotIn('id', $linked)->orderBy('name')->get(),
        ];
    }

    private function validateWorker(Request $request, ?Worker $worker = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', Rule::unique('workers', 'code')->ignore($worker)],
            'mobile' => ['required', 'string', 'max:30'],
            'worker_type' => ['required', Rule::in(array_keys(Options::WORKER_TYPES))],
            'skill' => ['nullable', 'string', 'max:120'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'project_location_id' => ['nullable', 'exists:project_locations,id'],
            'daily_wage' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('workers', 'user_id')->ignore($worker)],
        ]);
    }

    private function authorizeView(Worker $worker): void
    {
        if (auth()->user()->hasRole('super_admin') || ! $worker->project_id) {
            return;
        }

        abort_unless(Project::query()->visibleTo(auth()->user())->whereKey($worker->project_id)->exists(), 403);
    }
}
