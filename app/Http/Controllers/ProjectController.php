<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectStatusHistory;
use App\Models\ProjectType;
use App\Models\User;
use App\Services\ProgressService;
use App\Support\Access;
use App\Support\Options;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::query()
            ->visibleTo(auth()->user())
            ->with(['type', 'engineer', 'siteEngineer'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('client_name', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('projects.index', [
            'projects' => $projects,
            'statuses' => Options::PROJECT_STATUSES,
            'canCreate' => Access::createProject(auth()->user()),
        ]);
    }

    public function create()
    {
        abort_unless(Access::createProject(auth()->user()), 403);

        return view('projects.form', $this->formData(new Project));
    }

    public function store(Request $request, ProgressService $progress)
    {
        abort_unless(Access::createProject(auth()->user()), 403);
        $data = $this->validateProject($request);

        if (auth()->user()->hasRole('engineer') && empty($data['engineer_id'])) {
            $data['engineer_id'] = auth()->id();
        }

        $project = Project::create($data);
        $this->recordStatus($project, null, $project->status, 'Project created.');
        $progress->recalculate($project);

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function show(Project $project)
    {
        $this->authorizeView($project);
        $project->load([
            'type', 'engineer', 'siteEngineer',
            'locations.siteEngineer',
            'pillars.location', 'walls.location', 'bridges.location',
            'statusHistory.user',
            'reports.location',
        ]);

        $latest = $project->progressEntries()->latest('recorded_on')->first();

        return view('projects.show', [
            'project' => $project,
            'latest' => $latest,
            'canEdit' => Access::editProject(auth()->user(), $project),
            'canSubmit' => Access::submitReport(auth()->user(), $project),
        ]);
    }

    public function edit(Project $project)
    {
        abort_unless(Access::editProject(auth()->user(), $project), 403);

        return view('projects.form', $this->formData($project));
    }

    public function update(Request $request, Project $project, ProgressService $progress)
    {
        abort_unless(Access::editProject(auth()->user(), $project), 403);
        $from = $project->status;
        $project->update($this->validateProject($request, $project));

        if ($from !== $project->status) {
            $this->recordStatus($project, $from, $project->status, 'Status updated.');
        }

        $progress->recalculate($project);

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        abort_unless(Access::super(auth()->user()), 403);
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }

    private function formData(Project $project): array
    {
        return [
            'project' => $project,
            'types' => ProjectType::orderBy('name')->get(),
            'engineers' => User::withRole('engineer')->orderBy('name')->get(),
            'siteEngineers' => User::withRole('site_engineer')->orderBy('name')->get(),
            'statuses' => Options::PROJECT_STATUSES,
        ];
    }

    private function validateProject(Request $request, ?Project $project = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:40', Rule::unique('projects', 'code')->ignore($project)],
            'project_type_id' => ['required', 'exists:project_types,id'],
            'client_name' => ['required', 'string', 'max:160'],
            'location_summary' => ['nullable', 'string', 'max:180'],
            'start_date' => ['nullable', 'date'],
            'expected_completion_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'engineer_id' => ['nullable', 'exists:users,id'],
            'site_engineer_id' => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Options::PROJECT_STATUSES))],
        ]);
    }

    private function recordStatus(Project $project, ?string $from, string $to, string $note): void
    {
        ProjectStatusHistory::create([
            'project_id' => $project->id,
            'from_status' => $from,
            'to_status' => $to,
            'user_id' => auth()->id(),
            'note' => $note,
        ]);
    }

    private function authorizeView(Project $project): void
    {
        $visible = Project::query()->visibleTo(auth()->user())->whereKey($project->id)->exists();
        abort_unless($visible, 403);
    }
}
