@extends('layouts.app')
@section('eyebrow', $worker->code)
@section('title', $worker->name)
@section('sub', $worker->worker_type.($worker->skill ? ' · '.$worker->skill : ''))
@section('actions')
    @if (auth()->user()->hasRole('super_admin'))
        <a class="btn secondary" href="{{ route('workers.edit', $worker) }}">Edit</a>
    @endif
@endsection

@section('content')
<section class="stats">
    <article class="stat"><span>Mobile</span><strong style="font-size:24px">{{ $worker->mobile }}</strong></article>
    <article class="stat"><span>Project</span><strong style="font-size:24px">{{ $worker->project?->name ?? '—' }}</strong></article>
    <article class="stat"><span>Location</span><strong style="font-size:24px">{{ $worker->location?->name ?? '—' }}</strong></article>
    <article class="stat"><span>Daily wage</span><strong>{{ number_format($worker->daily_wage, 0) }}</strong></article>
</section>
<section class="layout-split">
    <div class="panel">
        <h2>Assignments</h2>
        @forelse ($worker->assignments as $assignment)
            <article class="work-card" style="margin-bottom:8px">
                <strong>{{ $assignment->workLabel() }}</strong>
                <div class="mini">
                    <span>{{ $assignment->project->name }}</span>
                    <span>{{ $assignment->location?->name }}</span>
                    <span>{{ $assignment->assigned_on->format('d M Y') }}</span>
                </div>
            </article>
        @empty
            <p class="empty">Not assigned to a specific work item yet.</p>
        @endforelse
        <h2 style="margin-top:18px">Recent attendance</h2>
        <table class="grid">
            <tbody>
            @forelse ($worker->attendance as $row)
                <tr>
                    <td>{{ $row->attended_on->format('d M Y') }}</td>
                    <td>@include('partials.badge', ['status' => $row->status, 'label' => $row->statusLabel()])</td>
                </tr>
            @empty
                <tr><td class="empty">No attendance.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($canAssign)
    <form class="panel" method="POST" action="{{ route('workers.assign', $worker) }}">
        @csrf
        <h2>Assign work</h2>
        <div class="form-grid" style="grid-template-columns:1fr">
            @include('partials.field', ['label' => 'Project', 'name' => 'project_id', 'as' => 'select', 'id' => 'assign_project', 'options' => $projects->pluck('name', 'id'), 'placeholder' => 'Select', 'required' => true])
            @include('partials.field', ['label' => 'Location', 'name' => 'project_location_id', 'as' => 'select', 'id' => 'assign_location', 'options' => []])
            @include('partials.field', ['label' => 'Work type', 'name' => 'work_type', 'as' => 'select', 'id' => 'assign_type', 'options' => ['pillar' => 'Pillar', 'wall' => 'Wall', 'bridge' => 'Bridge'], 'placeholder' => 'General'])
            @include('partials.field', ['label' => 'Work item', 'name' => 'work_id', 'as' => 'select', 'id' => 'assign_work', 'options' => []])
            @include('partials.field', ['label' => 'Assigned on', 'name' => 'assigned_on', 'type' => 'date', 'value' => now()->toDateString(), 'required' => true])
        </div>
        <div class="form-actions"><button class="btn">Assign</button></div>
    </form>
    @php
        $catalog = $projects->map(fn ($project) => [
            'id' => $project->id,
            'locations' => $project->locations->map(fn ($location) => ['id' => $location->id, 'name' => $location->name])->values(),
            'works' => [
                'pillar' => $project->pillars->map(fn ($work) => ['id' => $work->id, 'code' => $work->code])->values(),
                'wall' => $project->walls->map(fn ($work) => ['id' => $work->id, 'code' => $work->code])->values(),
                'bridge' => $project->bridges->map(fn ($work) => ['id' => $work->id, 'code' => $work->code])->values(),
            ],
        ])->values();
    @endphp
    <script>
    const catalog = @json($catalog);
    const projectSelect = document.getElementById('assign_project');
    const locationSelect = document.getElementById('assign_location');
    const typeSelect = document.getElementById('assign_type');
    const workSelect = document.getElementById('assign_work');
    function current() { return catalog.find(item => String(item.id) === projectSelect.value); }
    function fill() {
        const project = current();
        locationSelect.innerHTML = '<option value="">Optional</option>';
        (project?.locations || []).forEach(location => {
            const option = document.createElement('option');
            option.value = location.id; option.textContent = location.name;
            locationSelect.appendChild(option);
        });
        workSelect.innerHTML = '<option value="">General site work</option>';
        ((project?.works || {})[typeSelect.value] || []).forEach(work => {
            const option = document.createElement('option');
            option.value = work.id; option.textContent = work.code;
            workSelect.appendChild(option);
        });
    }
    projectSelect.addEventListener('change', fill);
    typeSelect.addEventListener('change', fill);
    </script>
    @endif
</section>
@endsection
