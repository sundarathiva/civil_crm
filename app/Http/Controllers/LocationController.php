<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectLocation;
use App\Models\User;
use App\Support\Access;
use App\Support\Options;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::query()->visibleTo(auth()->user())->orderBy('name')->get();
        $locations = ProjectLocation::with(['project', 'siteEngineer'])
            ->whereIn('project_id', $projects->pluck('id'))
            ->when($request->project_id, fn ($query, $id) => $query->where('project_id', $id))
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('chainage', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('locations.index', compact('locations', 'projects'));
    }

    public function create(Request $request)
    {
        return view('locations.form', $this->formData(new ProjectLocation([
            'project_id' => $request->integer('project_id') ?: null,
        ])));
    }

    public function store(Request $request)
    {
        $data = $this->validateLocation($request);
        $project = Project::findOrFail($data['project_id']);
        abort_unless(Access::editProject(auth()->user(), $project), 403);
        ProjectLocation::create($data);

        return redirect()->route('locations.index', ['project_id' => $project->id])->with('status', 'Location added.');
    }

    public function edit(ProjectLocation $location)
    {
        abort_unless(Access::editProject(auth()->user(), $location->project), 403);

        return view('locations.form', $this->formData($location));
    }

    public function update(Request $request, ProjectLocation $location)
    {
        abort_unless(Access::editProject(auth()->user(), $location->project), 403);
        $data = $this->validateLocation($request);
        abort_unless(Access::editProject(auth()->user(), Project::findOrFail($data['project_id'])), 403);
        $location->update($data);

        return redirect()->route('locations.index', ['project_id' => $location->project_id])->with('status', 'Location updated.');
    }

    public function destroy(ProjectLocation $location)
    {
        abort_unless(Access::editProject(auth()->user(), $location->project), 403);
        $location->delete();

        return redirect()->route('locations.index')->with('status', 'Location removed.');
    }

    private function formData(ProjectLocation $location): array
    {
        return [
            'location' => $location,
            'projects' => Project::query()->visibleTo(auth()->user())->orderBy('name')->get()
                ->filter(fn (Project $project) => Access::editProject(auth()->user(), $project)),
            'siteEngineers' => User::withRole('site_engineer')->orderBy('name')->get(),
            'statuses' => Options::LOCATION_STATUSES,
        ];
    }

    private function validateLocation(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'chainage' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'site_engineer_id' => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Options::LOCATION_STATUSES))],
        ]);
    }
}
