<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentUsage;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Support\Access;
use App\Support\Options;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::query()->visibleTo(auth()->user())->orderBy('name')->get();
        $equipment = Equipment::with(['project', 'location'])
            ->when(! auth()->user()->hasRole('super_admin'), function ($query) use ($projects) {
                $query->where(function ($inner) use ($projects) {
                    $inner->whereIn('project_id', $projects->pluck('id'))->orWhereNull('project_id');
                });
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('equipment.index', [
            'equipment' => $equipment,
            'statuses' => Options::EQUIPMENT_STATUSES,
            'canManage' => Access::manageMasters(auth()->user()),
        ]);
    }

    public function create()
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);

        return view('equipment.form', $this->formData(new Equipment(['status' => 'available'])));
    }

    public function store(Request $request)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);
        $equipment = Equipment::create($this->validateEquipment($request));

        return redirect()->route('equipment.show', $equipment)->with('status', 'Equipment added.');
    }

    public function show(Equipment $equipment)
    {
        $this->authorizeView($equipment);
        $usages = $equipment->usages()->with(['project', 'location', 'user'])->latest('used_on')->paginate(12);

        return view('equipment.show', [
            'equipment' => $equipment->load(['project', 'location']),
            'usages' => $usages,
            'projects' => Project::query()->visibleTo(auth()->user())->with('locations')->orderBy('name')->get(),
            'canLog' => auth()->user()->hasRole('super_admin', 'engineer', 'site_engineer'),
        ]);
    }

    public function edit(Equipment $equipment)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);

        return view('equipment.form', $this->formData($equipment));
    }

    public function update(Request $request, Equipment $equipment)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);
        $equipment->update($this->validateEquipment($request, $equipment));

        return redirect()->route('equipment.show', $equipment)->with('status', 'Equipment updated.');
    }

    public function destroy(Equipment $equipment)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);
        $equipment->delete();

        return redirect()->route('equipment.index')->with('status', 'Equipment removed.');
    }

    public function usage(Request $request, Equipment $equipment)
    {
        $this->authorizeView($equipment);
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'project_location_id' => ['nullable', 'exists:project_locations,id'],
            'used_on' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'working_hours' => ['nullable', 'numeric', 'min:0'],
            'fuel_used' => ['nullable', 'numeric', 'min:0'],
            'work_description' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        abort_unless(Access::submitReport(auth()->user(), $project) || Access::editProject(auth()->user(), $project), 403);

        if (! empty($data['project_location_id'])) {
            $location = ProjectLocation::findOrFail($data['project_location_id']);
            abort_unless((int) $location->project_id === (int) $project->id, 422);
        }

        if (empty($data['working_hours']) && ! empty($data['start_time']) && ! empty($data['end_time'])) {
            $start = strtotime($data['start_time']);
            $end = strtotime($data['end_time']);
            if ($end > $start) {
                $data['working_hours'] = round(($end - $start) / 3600, 2);
            }
        }

        EquipmentUsage::create($data + [
            'equipment_id' => $equipment->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', 'Equipment usage recorded.');
    }

    private function formData(Equipment $equipment): array
    {
        return [
            'equipment' => $equipment,
            'types' => Options::EQUIPMENT_TYPES,
            'statuses' => Options::EQUIPMENT_STATUSES,
            'projects' => Project::orderBy('name')->get(),
            'locations' => ProjectLocation::orderBy('name')->get(),
        ];
    }

    private function validateEquipment(Request $request, ?Equipment $equipment = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', Rule::unique('equipment', 'code')->ignore($equipment)],
            'equipment_type' => ['required', Rule::in(array_keys(Options::EQUIPMENT_TYPES))],
            'registration_number' => ['nullable', 'string', 'max:60'],
            'owner' => ['nullable', 'string', 'max:120'],
            'operator_name' => ['nullable', 'string', 'max:120'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'project_location_id' => ['nullable', 'exists:project_locations,id'],
            'status' => ['required', Rule::in(array_keys(Options::EQUIPMENT_STATUSES))],
        ]);
    }

    private function authorizeView(Equipment $equipment): void
    {
        if (auth()->user()->hasRole('super_admin') || ! $equipment->project_id) {
            return;
        }

        $visible = Project::query()->visibleTo(auth()->user())->whereKey($equipment->project_id)->exists();
        abort_unless($visible, 403);
    }
}
