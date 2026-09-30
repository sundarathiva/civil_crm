@extends('layouts.app')
@section('eyebrow', $meta['plural'])
@section('title', $item->exists ? 'Edit '.$item->code : 'New '.strtolower($meta['label']))

@section('content')
<form class="panel" method="POST" action="{{ $item->exists ? route('works.update', [$type, $item->id]) : route('works.store', $type) }}">
    @csrf
    @if ($item->exists) @method('PUT') @endif
    <div class="form-grid">
        @include('partials.field', ['label' => 'Project', 'name' => 'project_id', 'as' => 'select', 'id' => 'project_id', 'options' => $projects->pluck('name', 'id'), 'value' => old('project_id', $item->project_id), 'placeholder' => 'Select project', 'required' => true])
        @include('partials.field', ['label' => 'Location', 'name' => 'project_location_id', 'as' => 'select', 'id' => 'project_location_id', 'options' => [], 'required' => true])
        @include('partials.field', ['label' => $meta['label'].' number', 'name' => 'code', 'value' => old('code', $item->code), 'required' => true])
        @include('partials.field', ['label' => 'Status', 'name' => 'status', 'as' => 'select', 'options' => $statuses, 'value' => old('status', $item->status ?: 'not_started')])
        @include('partials.field', ['label' => 'Progress %', 'name' => 'progress', 'type' => 'number', 'min' => 0, 'max' => 100, 'step' => '0.1', 'value' => old('progress', $item->progress ?? 0), 'required' => true])
        @if ($type === 'walls')
            @include('partials.field', ['label' => 'Length (m)', 'name' => 'length', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'value' => old('length', $item->length), 'required' => true])
            @include('partials.field', ['label' => 'Height (m)', 'name' => 'height', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'value' => old('height', $item->height), 'required' => true])
        @endif
        @if ($type === 'bridges')
            @include('partials.field', ['label' => 'Bridge length (m)', 'name' => 'bridge_length', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'value' => old('bridge_length', $item->bridge_length), 'required' => true])
            @include('partials.field', ['label' => 'Bridge width (m)', 'name' => 'bridge_width', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'value' => old('bridge_width', $item->bridge_width), 'required' => true])
        @endif
        @include('partials.field', ['label' => 'Start date', 'name' => 'start_date', 'type' => 'date', 'value' => old('start_date', optional($item->start_date)->format('Y-m-d'))])
        @include('partials.field', ['label' => 'Expected completion', 'name' => 'expected_completion_date', 'type' => 'date', 'value' => old('expected_completion_date', optional($item->expected_completion_date)->format('Y-m-d'))])
        @include('partials.field', ['label' => 'Description', 'name' => 'description', 'as' => 'textarea', 'value' => old('description', $item->description), 'wide' => true])
    </div>
    @if (!empty($limited))
        <p class="mini">As site engineer you can update progress, status, and notes.</p>
    @endif
    <div class="form-actions">
        <button class="btn" type="submit">Save</button>
        <a class="btn secondary" href="{{ route('works.index', $type) }}">Cancel</a>
    </div>
</form>
@php
    $catalog = $projects->map(fn ($project) => [
        'id' => $project->id,
        'locations' => $project->locations->map(fn ($location) => ['id' => $location->id, 'name' => $location->name])->values(),
    ])->values();
    $selectedLocation = old('project_location_id', $item->project_location_id);
@endphp
<script>
const catalog = @json($catalog);
const selectedLocation = @json($selectedLocation);
const projectSelect = document.getElementById('project_id');
const locationSelect = document.getElementById('project_location_id');
function fillLocations() {
    const project = catalog.find(item => String(item.id) === projectSelect.value);
    locationSelect.innerHTML = '<option value="">Select location</option>';
    (project?.locations || []).forEach(location => {
        const option = document.createElement('option');
        option.value = location.id;
        option.textContent = location.name;
        if (String(location.id) === String(selectedLocation)) option.selected = true;
        locationSelect.appendChild(option);
    });
}
projectSelect.addEventListener('change', fillLocations);
fillLocations();
</script>
@endsection
