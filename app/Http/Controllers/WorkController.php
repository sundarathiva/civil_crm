<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectLocation;
use App\Services\ProgressService;
use App\Support\Access;
use App\Support\Options;
use App\Support\Works;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkController extends Controller
{
    public function index(Request $request, string $type)
    {
        $meta = Works::meta($type);
        $projects = Project::query()->visibleTo(auth()->user())->orderBy('name')->get();
        $items = $meta['model']::with(['project', 'location'])
            ->whereIn('project_id', $projects->pluck('id'))
            ->when($request->project_id, fn ($query, $id) => $query->where('project_id', $id))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('code')
            ->paginate(12)
            ->withQueryString();

        return view('works.index', [
            'type' => $type,
            'meta' => $meta,
            'items' => $items,
            'projects' => $projects,
            'statuses' => Options::WORK_STATUSES,
            'canCreate' => Access::createProject(auth()->user()),
        ]);
    }

    public function create(Request $request, string $type)
    {
        abort_unless(Access::createProject(auth()->user()), 403);
        $meta = Works::meta($type);
        $item = new $meta['model']([
            'project_id' => $request->integer('project_id') ?: null,
            'project_location_id' => $request->integer('project_location_id') ?: null,
            'status' => 'not_started',
            'progress' => 0,
        ]);

        return view('works.form', $this->formData($type, $item));
    }

    public function store(Request $request, string $type, ProgressService $progress)
    {
        abort_unless(Access::createProject(auth()->user()), 403);
        $meta = Works::meta($type);
        $data = $this->validateWork($request, $type);
        $this->guardProject($data);
        $item = $meta['model']::create($data);
        $progress->recalculate($item->project);

        return redirect()->route('works.index', $type)->with('status', $meta['label'].' added.');
    }

    public function edit(string $type, int $id)
    {
        $item = $this->find($type, $id);
        abort_unless(Works::canReach(auth()->user(), $item), 403);

        return view('works.form', $this->formData($type, $item));
    }

    public function update(Request $request, string $type, int $id, ProgressService $progress)
    {
        $item = $this->find($type, $id);
        abort_unless(Works::canReach(auth()->user(), $item), 403);
        $data = $this->validateWork($request, $type, $item);

        if (auth()->user()->hasRole('site_engineer') && ! Access::editProject(auth()->user(), $item->project)) {
            $data = [
                'status' => $data['status'],
                'progress' => $data['progress'],
                'description' => $data['description'],
            ];
        } else {
            $this->guardProject($data);
        }

        $item->update($data);
        $item->refresh();
        $progress->recalculate($item->project);

        return redirect()->route('works.index', $type)->with('status', 'Work updated.');
    }

    public function destroy(string $type, int $id, ProgressService $progress)
    {
        $item = $this->find($type, $id);
        abort_unless(Access::editProject(auth()->user(), $item->project), 403);
        $project = $item->project;
        $item->delete();
        $progress->recalculate($project);

        return redirect()->route('works.index', $type)->with('status', 'Work removed.');
    }

    private function find(string $type, int $id): Model
    {
        $meta = Works::meta($type);

        return $meta['model']::with(['project', 'location'])->findOrFail($id);
    }

    private function formData(string $type, Model $item): array
    {
        $projects = Project::query()->visibleTo(auth()->user())->with('locations')->orderBy('name')->get();

        return [
            'type' => $type,
            'meta' => Works::meta($type),
            'item' => $item,
            'projects' => $projects,
            'statuses' => Options::WORK_STATUSES,
            'limited' => $item->exists
                && auth()->user()->hasRole('site_engineer')
                && ! Access::editProject(auth()->user(), $item->project),
        ];
    }

    private function validateWork(Request $request, string $type, ?Model $item = null): array
    {
        $rules = [
            'project_id' => ['required', 'exists:projects,id'],
            'project_location_id' => ['required', 'exists:project_locations,id'],
            'code' => ['required', 'string', 'max:40'],
            'start_date' => ['nullable', 'date'],
            'expected_completion_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Options::WORK_STATUSES))],
            'progress' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
        ];

        if ($type === 'walls') {
            $rules['length'] = ['required', 'numeric', 'min:0'];
            $rules['height'] = ['required', 'numeric', 'min:0'];
        }

        if ($type === 'bridges') {
            $rules['bridge_length'] = ['required', 'numeric', 'min:0'];
            $rules['bridge_width'] = ['required', 'numeric', 'min:0'];
        }

        $data = $request->validate($rules);
        $exists = Works::meta($type)['model']::where('project_id', $data['project_id'])
            ->where('code', $data['code'])
            ->when($item, fn ($query) => $query->whereKeyNot($item->getKey()))
            ->exists();

        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => 'This code is already used on the project.',
            ]);
        }

        return $data;
    }

    private function guardProject(array $data): void
    {
        $project = Project::findOrFail($data['project_id']);
        abort_unless(Access::editProject(auth()->user(), $project), 403);
        $location = ProjectLocation::findOrFail($data['project_location_id']);
        abort_unless((int) $location->project_id === (int) $project->id, 422, 'That location is not part of the selected project.');
    }
}
