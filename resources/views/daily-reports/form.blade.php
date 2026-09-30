@extends('layouts.app')
@section('eyebrow', 'Daily site report')
@section('title', $report->exists ? $report->code : 'New report')

@php
    $materialRows = old('materials', $report->exists ? $report->materials->map(fn ($line) => ['material_id' => $line->material_id, 'quantity' => $line->quantity])->all() : [['material_id' => '', 'quantity' => '']]);
    $equipmentRows = old('equipment_lines', $report->exists ? $report->equipmentLines->map(fn ($line) => ['equipment_id' => $line->equipment_id, 'working_hours' => $line->working_hours, 'fuel_used' => $line->fuel_used])->all() : [['equipment_id' => '', 'working_hours' => '', 'fuel_used' => '']]);
    $workerRows = old('worker_lines', $report->exists ? $report->workers->map(fn ($line) => ['worker_id' => $line->worker_id, 'attendance_status' => $line->attendance_status])->all() : [['worker_id' => '', 'attendance_status' => 'present']]);
    if ($materialRows === []) $materialRows = [['material_id' => '', 'quantity' => '']];
    if ($equipmentRows === []) $equipmentRows = [['equipment_id' => '', 'working_hours' => '', 'fuel_used' => '']];
    if ($workerRows === []) $workerRows = [['worker_id' => '', 'attendance_status' => 'present']];
@endphp

@section('content')
<form class="panel" method="POST" enctype="multipart/form-data" action="{{ $report->exists ? route('daily-reports.update', $report) : route('daily-reports.store') }}">
    @csrf
    @if ($report->exists) @method('PUT') @endif
    <div class="form-grid">
        @include('partials.field', ['label' => 'Project', 'name' => 'project_id', 'as' => 'select', 'id' => 'dsr_project', 'options' => $projects->pluck('name', 'id'), 'value' => old('project_id', $report->project_id), 'placeholder' => 'Select project', 'required' => true])
        @include('partials.field', ['label' => 'Location', 'name' => 'project_location_id', 'as' => 'select', 'id' => 'dsr_location', 'options' => [], 'required' => true])
        @include('partials.field', ['label' => 'Date', 'name' => 'report_date', 'type' => 'date', 'value' => old('report_date', optional($report->report_date)->format('Y-m-d') ?: now()->toDateString()), 'required' => true])
        @include('partials.field', ['label' => 'Work type', 'name' => 'work_type', 'as' => 'select', 'id' => 'dsr_type', 'options' => $workTypes, 'value' => old('work_type', $report->work_type), 'placeholder' => 'Select'])
        @include('partials.field', ['label' => 'Work item', 'name' => 'work_id', 'as' => 'select', 'id' => 'dsr_work', 'options' => []])
        @include('partials.field', ['label' => 'Progress %', 'name' => 'progress', 'type' => 'number', 'min' => 0, 'max' => 100, 'step' => '0.1', 'value' => old('progress', $report->progress ?? 0), 'required' => true])
        @include('partials.field', ['label' => 'Worker count', 'name' => 'worker_count', 'type' => 'number', 'min' => 0, 'value' => old('worker_count', $report->worker_count ?? 0), 'required' => true])
        @include('partials.field', ['label' => 'Work description', 'name' => 'work_description', 'as' => 'textarea', 'value' => old('work_description', $report->work_description), 'wide' => true, 'required' => true])
        @include('partials.field', ['label' => 'Issues', 'name' => 'issues', 'as' => 'textarea', 'value' => old('issues', $report->issues), 'wide' => true])
        @include('partials.field', ['label' => 'Remarks', 'name' => 'remarks', 'as' => 'textarea', 'value' => old('remarks', $report->remarks), 'wide' => true])
    </div>

    <h2 style="margin:22px 0 10px">Materials used</h2>
    <div class="repeat" id="material-rows">
        @foreach ($materialRows as $index => $row)
            <div class="repeat-row">
                <select name="materials[{{ $index }}][material_id]">
                    <option value="">Material</option>
                    @foreach ($materials as $material)
                        <option value="{{ $material->id }}" @selected(($row['material_id'] ?? '') == $material->id)>{{ $material->name }} ({{ $material->unit }})</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" min="0" name="materials[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? '' }}" placeholder="Qty">
            </div>
        @endforeach
    </div>
    <button class="btn small secondary" type="button" id="add-material" style="margin-top:8px">Add material</button>

    <h2 style="margin:22px 0 10px">Equipment used</h2>
    <div class="repeat" id="equipment-rows">
        @foreach ($equipmentRows as $index => $row)
            <div class="repeat-row">
                <select name="equipment_lines[{{ $index }}][equipment_id]">
                    <option value="">Equipment</option>
                    @foreach ($equipment as $item)
                        <option value="{{ $item->id }}" @selected(($row['equipment_id'] ?? '') == $item->id)>{{ $item->code }} · {{ $item->name }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.1" min="0" name="equipment_lines[{{ $index }}][working_hours]" value="{{ $row['working_hours'] ?? '' }}" placeholder="Hours">
                <input type="number" step="0.1" min="0" name="equipment_lines[{{ $index }}][fuel_used]" value="{{ $row['fuel_used'] ?? '' }}" placeholder="Fuel">
            </div>
        @endforeach
    </div>
    <button class="btn small secondary" type="button" id="add-equipment" style="margin-top:8px">Add equipment</button>

    <h2 style="margin:22px 0 10px">Workers on site</h2>
    <div class="repeat" id="worker-rows">
        @foreach ($workerRows as $index => $row)
            <div class="repeat-row">
                <select name="worker_lines[{{ $index }}][worker_id]">
                    <option value="">Worker</option>
                    @foreach ($workers as $worker)
                        <option value="{{ $worker->id }}" @selected(($row['worker_id'] ?? '') == $worker->id)>{{ $worker->name }} · {{ $worker->worker_type }}</option>
                    @endforeach
                </select>
                <select name="worker_lines[{{ $index }}][attendance_status]">
                    @foreach ($attendance as $key => $label)
                        <option value="{{ $key }}" @selected(($row['attendance_status'] ?? 'present') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
    </div>
    <button class="btn small secondary" type="button" id="add-worker" style="margin-top:8px">Add worker</button>

    <h2 style="margin:22px 0 10px">Site photos</h2>
    <input type="file" name="photos[]" accept="image/*" multiple>
    @if ($report->exists && $report->photos->isNotEmpty())
        <div class="photos" style="margin-top:12px">
            @foreach ($report->photos as $photo)
                <label>
                    <img src="{{ $photo->url() }}" alt="{{ $photo->original_name }}">
                    <span class="mini"><input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}"> Remove</span>
                </label>
            @endforeach
        </div>
    @endif

    <div class="form-actions">
        <button class="btn secondary" name="action" value="draft">Save draft</button>
        <button class="btn" name="action" value="submit">Submit for review</button>
    </div>
</form>
<template id="material-template">
    <div class="repeat-row">
        <select name="materials[__i__][material_id]"><option value="">Material</option>@foreach ($materials as $material)<option value="{{ $material->id }}">{{ $material->name }} ({{ $material->unit }})</option>@endforeach</select>
        <input type="number" step="0.01" min="0" name="materials[__i__][quantity]" placeholder="Qty">
    </div>
</template>
<template id="equipment-template">
    <div class="repeat-row">
        <select name="equipment_lines[__i__][equipment_id]"><option value="">Equipment</option>@foreach ($equipment as $item)<option value="{{ $item->id }}">{{ $item->code }} · {{ $item->name }}</option>@endforeach</select>
        <input type="number" step="0.1" min="0" name="equipment_lines[__i__][working_hours]" placeholder="Hours">
        <input type="number" step="0.1" min="0" name="equipment_lines[__i__][fuel_used]" placeholder="Fuel">
    </div>
</template>
<template id="worker-template">
    <div class="repeat-row">
        <select name="worker_lines[__i__][worker_id]"><option value="">Worker</option>@foreach ($workers as $worker)<option value="{{ $worker->id }}">{{ $worker->name }} · {{ $worker->worker_type }}</option>@endforeach</select>
        <select name="worker_lines[__i__][attendance_status]">@foreach ($attendance as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
    </div>
</template>
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
    $selectedLocation = old('project_location_id', $report->project_location_id);
    $selectedWork = old('work_id', $report->work_id);
@endphp
<script>
const catalog = @json($catalog);
const selectedLocation = @json($selectedLocation);
const selectedWork = @json($selectedWork);
const projectSelect = document.getElementById('dsr_project');
const locationSelect = document.getElementById('dsr_location');
const typeSelect = document.getElementById('dsr_type');
const workSelect = document.getElementById('dsr_work');
function current() { return catalog.find(item => String(item.id) === projectSelect.value); }
function fill() {
    const project = current();
    locationSelect.innerHTML = '<option value="">Select location</option>';
    (project?.locations || []).forEach(location => {
        const option = document.createElement('option');
        option.value = location.id; option.textContent = location.name;
        if (String(location.id) === String(selectedLocation)) option.selected = true;
        locationSelect.appendChild(option);
    });
    workSelect.innerHTML = '<option value="">Not linked</option>';
    ((project?.works || {})[typeSelect.value] || []).forEach(work => {
        const option = document.createElement('option');
        option.value = work.id; option.textContent = work.code;
        if (String(work.id) === String(selectedWork)) option.selected = true;
        workSelect.appendChild(option);
    });
}
projectSelect.addEventListener('change', fill);
typeSelect.addEventListener('change', fill);
fill();
function addRow(buttonId, hostId, templateId) {
    let index = document.querySelectorAll('#' + hostId + ' .repeat-row').length;
    document.getElementById(buttonId).addEventListener('click', () => {
        const html = document.getElementById(templateId).innerHTML.replaceAll('__i__', index++);
        document.getElementById(hostId).insertAdjacentHTML('beforeend', html);
    });
}
addRow('add-material', 'material-rows', 'material-template');
addRow('add-equipment', 'equipment-rows', 'equipment-template');
addRow('add-worker', 'worker-rows', 'worker-template');
</script>
@endsection
