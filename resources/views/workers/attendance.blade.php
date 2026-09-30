@extends('layouts.app')
@section('eyebrow', 'Crew')
@section('title', 'Attendance')
@section('sub', 'Present, absent, half day, leave, or overtime.')

@section('content')
<div class="panel" style="margin-bottom:14px">
    <form class="filters" method="GET">
        <input type="date" name="date" value="{{ $date }}">
        <select name="project_id" id="att_project" required>
            <option value="">Project</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected($projectId == $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
        <select name="project_location_id" id="att_location">
            <option value="">All locations</option>
        </select>
        <button class="btn small">Load crew</button>
    </form>
</div>
@if ($projectId)
<form class="panel" method="POST" action="{{ route('workers.attendance.store') }}">
    @csrf
    <input type="hidden" name="project_id" value="{{ $projectId }}">
    <input type="hidden" name="project_location_id" value="{{ $locationId }}">
    <input type="hidden" name="attended_on" value="{{ $date }}">
    <table class="grid">
        <thead><tr><th>Worker</th><th>Type</th><th>Status</th><th>Overtime hours</th></tr></thead>
        <tbody>
        @forelse ($workers as $index => $worker)
            @php $marked = $existing[$worker->id] ?? null; @endphp
            <tr>
                <td>
                    <strong>{{ $worker->name }}</strong>
                    <input type="hidden" name="rows[{{ $index }}][worker_id]" value="{{ $worker->id }}">
                </td>
                <td>{{ $worker->worker_type }}</td>
                <td>
                    <select name="rows[{{ $index }}][status]">
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(($marked->status ?? 'present') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" step="0.5" min="0" name="rows[{{ $index }}][overtime_hours]" value="{{ $marked->overtime_hours ?? 0 }}"></td>
            </tr>
        @empty
            <tr><td colspan="4" class="empty">No workers are assigned to this project.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if ($workers->isNotEmpty())
        <div class="form-actions"><button class="btn">Save attendance</button></div>
    @endif
</form>
@endif
@php
    $catalog = $projects->map(fn ($project) => [
        'id' => $project->id,
        'locations' => $project->locations->map(fn ($location) => ['id' => $location->id, 'name' => $location->name])->values(),
    ])->values();
@endphp
<script>
const catalog = @json($catalog);
const projectSelect = document.getElementById('att_project');
const locationSelect = document.getElementById('att_location');
const selected = @json($locationId);
function fill() {
    const project = catalog.find(item => String(item.id) === projectSelect.value);
    locationSelect.innerHTML = '<option value="">All locations</option>';
    (project?.locations || []).forEach(location => {
        const option = document.createElement('option');
        option.value = location.id;
        option.textContent = location.name;
        if (String(location.id) === String(selected)) option.selected = true;
        locationSelect.appendChild(option);
    });
}
projectSelect.addEventListener('change', fill);
fill();
</script>
@endsection
