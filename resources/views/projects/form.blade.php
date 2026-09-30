@extends('layouts.app')
@section('eyebrow', 'Portfolio')
@section('title', $project->exists ? 'Edit project' : 'New project')

@section('content')
<form class="panel" method="POST" action="{{ $project->exists ? route('projects.update', $project) : route('projects.store') }}">
    @csrf
    @if ($project->exists) @method('PUT') @endif
    <div class="form-grid">
        @include('partials.field', ['label' => 'Project name', 'name' => 'name', 'value' => old('name', $project->name), 'required' => true])
        @include('partials.field', ['label' => 'Project code', 'name' => 'code', 'value' => old('code', $project->code), 'required' => true])
        @include('partials.field', ['label' => 'Project type', 'name' => 'project_type_id', 'as' => 'select', 'options' => $types->pluck('name', 'id'), 'value' => old('project_type_id', $project->project_type_id), 'placeholder' => 'Select type', 'required' => true])
        @include('partials.field', ['label' => 'Client', 'name' => 'client_name', 'value' => old('client_name', $project->client_name), 'required' => true])
        @include('partials.field', ['label' => 'Location summary', 'name' => 'location_summary', 'value' => old('location_summary', $project->location_summary)])
        @include('partials.field', ['label' => 'Status', 'name' => 'status', 'as' => 'select', 'options' => $statuses, 'value' => old('status', $project->status ?: 'new'), 'required' => true])
        @include('partials.field', ['label' => 'Start date', 'name' => 'start_date', 'type' => 'date', 'value' => old('start_date', optional($project->start_date)->format('Y-m-d'))])
        @include('partials.field', ['label' => 'Expected completion', 'name' => 'expected_completion_date', 'type' => 'date', 'value' => old('expected_completion_date', optional($project->expected_completion_date)->format('Y-m-d'))])
        @include('partials.field', ['label' => 'Engineer', 'name' => 'engineer_id', 'as' => 'select', 'options' => $engineers->pluck('name', 'id'), 'value' => old('engineer_id', $project->engineer_id), 'placeholder' => 'Assign engineer'])
        @include('partials.field', ['label' => 'Site engineer', 'name' => 'site_engineer_id', 'as' => 'select', 'options' => $siteEngineers->pluck('name', 'id'), 'value' => old('site_engineer_id', $project->site_engineer_id), 'placeholder' => 'Assign site engineer'])
        @include('partials.field', ['label' => 'Description', 'name' => 'description', 'as' => 'textarea', 'value' => old('description', $project->description), 'wide' => true])
    </div>
    <div class="form-actions">
        <button class="btn" type="submit">Save project</button>
        <a class="btn secondary" href="{{ route('projects.index') }}">Cancel</a>
    </div>
</form>
@endsection
