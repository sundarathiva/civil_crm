<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectLocation;
use App\Services\StockService;
use App\Support\Access;
use App\Support\Options;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $materials = Material::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->low, fn ($query) => $query->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('materials.index', [
            'materials' => $materials,
            'canManage' => Access::manageMasters(auth()->user()),
        ]);
    }

    public function create()
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);

        return view('materials.form', [
            'material' => new Material(['status' => 'active', 'minimum_stock' => 0]),
            'categories' => Options::MATERIAL_CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);
        Material::create($this->validateMaterial($request));

        return redirect()->route('materials.index')->with('status', 'Material added. Record opening stock from its page.');
    }

    public function show(Material $material)
    {
        $material->load('stock');
        $transactions = $material->transactions()->with(['project', 'user'])->latest('transacted_on')->latest('id')->paginate(15);

        return view('materials.show', [
            'material' => $material,
            'transactions' => $transactions,
            'types' => $this->allowedTypes(),
            'projects' => Project::query()->visibleTo(auth()->user())->with('locations')->orderBy('name')->get(),
            'canManage' => Access::manageMasters(auth()->user()),
        ]);
    }

    public function edit(Material $material)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);

        return view('materials.form', [
            'material' => $material,
            'categories' => Options::MATERIAL_CATEGORIES,
        ]);
    }

    public function update(Request $request, Material $material)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);
        $material->update($this->validateMaterial($request, $material));

        return redirect()->route('materials.show', $material)->with('status', 'Material updated.');
    }

    public function destroy(Material $material)
    {
        abort_unless(Access::manageMasters(auth()->user()), 403);
        $material->delete();

        return redirect()->route('materials.index')->with('status', 'Material removed.');
    }

    public function transact(Request $request, Material $material, StockService $stock)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys($this->allowedTypes()))],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'transacted_on' => ['required', 'date'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'project_location_id' => ['nullable', 'exists:project_locations,id'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if (! empty($data['project_id'])) {
            $project = Project::findOrFail($data['project_id']);
            abort_unless(
                Access::manageMasters(auth()->user()) || Access::submitReport(auth()->user(), $project),
                403
            );
        }

        if (! empty($data['project_location_id'])) {
            $location = ProjectLocation::findOrFail($data['project_location_id']);
            abort_unless(empty($data['project_id']) || (int) $location->project_id === (int) $data['project_id'], 422);
        }

        $stock->apply(
            $material,
            $data['type'],
            (float) $data['quantity'],
            $data['transacted_on'],
            $data['project_id'] ?? null,
            $data['project_location_id'] ?? null,
            $data['remarks'] ?? null,
            auth()->user(),
        );

        return back()->with('status', 'Stock updated. Current balance is calculated from opening, receipts, issues, usage, and returns.');
    }

    private function allowedTypes(): array
    {
        if (Access::manageMasters(auth()->user())) {
            return Options::STOCK_TYPES;
        }

        return ['used' => Options::STOCK_TYPES['used']];
    }

    private function validateMaterial(Request $request, ?Material $material = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', Rule::unique('materials', 'code')->ignore($material)],
            'category' => ['required', Rule::in(array_keys(Options::MATERIAL_CATEGORIES))],
            'unit' => ['required', 'string', 'max:30'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
