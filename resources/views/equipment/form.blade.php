@extends('layouts.app')
@section('title', $equipment->exists ? 'Edit '.$equipment->code : 'New equipment')

@section('content')
<form class="panel" method="POST" action="{{ $equipment->exists ? route('equipment.update', $equipment) : route('equipment.store') }}">
    @csrf
    @if ($equipment->exists) @method('PUT') @endif
    <div class="form-grid">
        @include('partials.field', ['label' => 'Name', 'name' => 'name', 'value' => old('name', $equipment->name), 'required' => true])
        @include('partials.field', ['label' => 'Code', 'name' => 'code', 'value' => old('code', $equipment->code), 'required' => true])
        @include('partials.field', ['label' => 'Type', 'name' => 'equipment_type', 'as' => 'select', 'options' => $types, 'value' => old('equipment_type', $equipment->equipment_type), 'placeholder' => 'Select', 'required' => true])
        @include('partials.field', ['label' => 'Registration', 'name' => 'registration_number', 'value' => old('registration_number', $equipment->registration_number)])
        @include('partials.field', ['label' => 'Owner', 'name' => 'owner', 'value' => old('owner', $equipment->owner)])
        @include('partials.field', ['label' => 'Operator', 'name' => 'operator_name', 'value' => old('operator_name', $equipment->operator_name)])
        @include('partials.field', ['label' => 'Project', 'name' => 'project_id', 'as' => 'select', 'options' => $projects->pluck('name', 'id'), 'value' => old('project_id', $equipment->project_id), 'placeholder' => 'Unassigned'])
        @include('partials.field', ['label' => 'Location', 'name' => 'project_location_id', 'as' => 'select', 'options' => $locations->pluck('name', 'id'), 'value' => old('project_location_id', $equipment->project_location_id), 'placeholder' => 'Optional'])
        @include('partials.field', ['label' => 'Status', 'name' => 'status', 'as' => 'select', 'options' => $statuses, 'value' => old('status', $equipment->status ?: 'available')])
    </div>
    <div class="form-actions">
        <button class="btn">Save equipment</button>
        <a class="btn secondary" href="{{ route('equipment.index') }}">Cancel</a>
    </div>
</form>
@endsection
