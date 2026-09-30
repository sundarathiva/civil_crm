@extends('layouts.app')
@section('eyebrow', $equipment->code)
@section('title', $equipment->name)
@section('sub', $equipment->equipment_type.($equipment->registration_number ? ' · '.$equipment->registration_number : ''))
@section('actions')
    @if (auth()->user()->hasRole('super_admin'))
        <a class="btn secondary" href="{{ route('equipment.edit', $equipment) }}">Edit</a>
    @endif
@endsection

@section('content')
<section class="stats">
    <article class="stat"><span>Status</span><strong style="font-size:28px">{{ $equipment->statusLabel() }}</strong></article>
    <article class="stat"><span>Project</span><strong style="font-size:24px">{{ $equipment->project?->name ?? 'Unassigned' }}</strong></article>
    <article class="stat"><span>Location</span><strong style="font-size:24px">{{ $equipment->location?->name ?? '—' }}</strong></article>
    <article class="stat"><span>Operator</span><strong style="font-size:24px">{{ $equipment->operator_name ?: '—' }}</strong></article>
</section>
<section class="layout-split">
    <div class="panel">
        <h2>Usage</h2>
        <table class="grid">
            <thead><tr><th>Date</th><th>Project</th><th>Hours</th><th>Fuel</th><th>Work</th></tr></thead>
            <tbody>
            @forelse ($usages as $usage)
                <tr>
                    <td>{{ $usage->used_on->format('d M Y') }}</td>
                    <td>{{ $usage->project->name }}</td>
                    <td>{{ $usage->working_hours ?: '—' }}</td>
                    <td>{{ $usage->fuel_used ? $usage->fuel_used.' '.$usage->fuel_unit : '—' }}</td>
                    <td>{{ $usage->work_description }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No usage logged.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $usages->links() }}
    </div>
    @if ($canLog)
    <form class="panel" method="POST" action="{{ route('equipment.usage', $equipment) }}">
        @csrf
        <h2>Log usage</h2>
        <div class="form-grid" style="grid-template-columns:1fr">
            @include('partials.field', ['label' => 'Project', 'name' => 'project_id', 'as' => 'select', 'id' => 'use_project', 'options' => $projects->pluck('name', 'id'), 'value' => old('project_id', $equipment->project_id), 'placeholder' => 'Select', 'required' => true])
            @include('partials.field', ['label' => 'Location', 'name' => 'project_location_id', 'as' => 'select', 'id' => 'use_location', 'options' => []])
            @include('partials.field', ['label' => 'Date', 'name' => 'used_on', 'type' => 'date', 'value' => old('used_on', now()->toDateString()), 'required' => true])
            @include('partials.field', ['label' => 'Start', 'name' => 'start_time', 'type' => 'time', 'value' => old('start_time')])
            @include('partials.field', ['label' => 'End', 'name' => 'end_time', 'type' => 'time', 'value' => old('end_time')])
            @include('partials.field', ['label' => 'Working hours', 'name' => 'working_hours', 'type' => 'number', 'step' => '0.1', 'min' => 0, 'value' => old('working_hours')])
            @include('partials.field', ['label' => 'Fuel used (litres)', 'name' => 'fuel_used', 'type' => 'number', 'step' => '0.1', 'min' => 0, 'value' => old('fuel_used')])
            @include('partials.field', ['label' => 'Work description', 'name' => 'work_description', 'as' => 'textarea', 'value' => old('work_description')])
            @include('partials.field', ['label' => 'Remarks', 'name' => 'remarks', 'value' => old('remarks')])
        </div>
        <div class="form-actions"><button class="btn">Save usage</button></div>
    </form>
    @endif
</section>
@php
    $catalog = $projects->map(fn ($project) => [
        'id' => $project->id,
        'locations' => $project->locations->map(fn ($location) => ['id' => $location->id, 'name' => $location->name])->values(),
    ])->values();
    $selectedLocation = old('project_location_id', $equipment->project_location_id);
@endphp
<script>
const catalog = @json($catalog);
const projectSelect = document.getElementById('use_project');
const locationSelect = document.getElementById('use_location');
const selected = @json($selectedLocation);
function fill() {
    if (!projectSelect || !locationSelect) return;
    const project = catalog.find(item => String(item.id) === projectSelect.value);
    locationSelect.innerHTML = '<option value="">Optional</option>';
    (project?.locations || []).forEach(location => {
        const option = document.createElement('option');
        option.value = location.id;
        option.textContent = location.name;
        if (String(location.id) === String(selected)) option.selected = true;
        locationSelect.appendChild(option);
    });
}
projectSelect?.addEventListener('change', fill);
fill();
</script>
@endsection
