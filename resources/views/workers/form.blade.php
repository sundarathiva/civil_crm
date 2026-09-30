@extends('layouts.app')
@section('title', $worker->exists ? 'Edit '.$worker->name : 'New worker')

@section('content')
<form class="panel" method="POST" action="{{ $worker->exists ? route('workers.update', $worker) : route('workers.store') }}">
    @csrf
    @if ($worker->exists) @method('PUT') @endif
    <div class="form-grid">
        @include('partials.field', ['label' => 'Name', 'name' => 'name', 'value' => old('name', $worker->name), 'required' => true])
        @include('partials.field', ['label' => 'Worker ID', 'name' => 'code', 'value' => old('code', $worker->code), 'required' => true])
        @include('partials.field', ['label' => 'Mobile', 'name' => 'mobile', 'value' => old('mobile', $worker->mobile), 'required' => true])
        @include('partials.field', ['label' => 'Type', 'name' => 'worker_type', 'as' => 'select', 'options' => $types, 'value' => old('worker_type', $worker->worker_type), 'placeholder' => 'Select', 'required' => true])
        @include('partials.field', ['label' => 'Skill', 'name' => 'skill', 'value' => old('skill', $worker->skill)])
        @include('partials.field', ['label' => 'Daily wage', 'name' => 'daily_wage', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'value' => old('daily_wage', $worker->daily_wage ?? 0), 'required' => true])
        @include('partials.field', ['label' => 'Project', 'name' => 'project_id', 'as' => 'select', 'options' => $projects->pluck('name', 'id'), 'value' => old('project_id', $worker->project_id), 'placeholder' => 'Unassigned'])
        @include('partials.field', ['label' => 'Location', 'name' => 'project_location_id', 'as' => 'select', 'options' => $locations->pluck('name', 'id'), 'value' => old('project_location_id', $worker->project_location_id), 'placeholder' => 'Optional'])
        @include('partials.field', ['label' => 'Login account', 'name' => 'user_id', 'as' => 'select', 'options' => $accounts->pluck('name', 'id'), 'value' => old('user_id', $worker->user_id), 'placeholder' => 'No login'])
        @include('partials.field', ['label' => 'Status', 'name' => 'status', 'as' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'value' => old('status', $worker->status ?: 'active')])
    </div>
    <div class="form-actions">
        <button class="btn">Save worker</button>
        <a class="btn secondary" href="{{ route('workers.index') }}">Cancel</a>
    </div>
</form>
@endsection
