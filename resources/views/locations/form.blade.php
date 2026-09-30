@extends('layouts.app')
@section('eyebrow', 'Ground')
@section('title', $location->exists ? 'Edit location' : 'New location')

@section('content')
<form class="panel" method="POST" action="{{ $location->exists ? route('locations.update', $location) : route('locations.store') }}">
    @csrf
    @if ($location->exists) @method('PUT') @endif
    <div class="form-grid">
        @include('partials.field', ['label' => 'Project', 'name' => 'project_id', 'as' => 'select', 'options' => $projects->pluck('name', 'id'), 'value' => old('project_id', $location->project_id), 'placeholder' => 'Select project', 'required' => true])
        @include('partials.field', ['label' => 'Location name', 'name' => 'name', 'value' => old('name', $location->name), 'required' => true])
        @include('partials.field', ['label' => 'Address', 'name' => 'address', 'value' => old('address', $location->address), 'wide' => true])
        @include('partials.field', ['label' => 'Chainage / area', 'name' => 'chainage', 'value' => old('chainage', $location->chainage)])
        @include('partials.field', ['label' => 'Site engineer', 'name' => 'site_engineer_id', 'as' => 'select', 'options' => $siteEngineers->pluck('name', 'id'), 'value' => old('site_engineer_id', $location->site_engineer_id), 'placeholder' => 'Assign'])
        @include('partials.field', ['label' => 'Latitude', 'name' => 'latitude', 'type' => 'number', 'step' => '0.0000001', 'value' => old('latitude', $location->latitude)])
        @include('partials.field', ['label' => 'Longitude', 'name' => 'longitude', 'type' => 'number', 'step' => '0.0000001', 'value' => old('longitude', $location->longitude)])
        @include('partials.field', ['label' => 'Status', 'name' => 'status', 'as' => 'select', 'options' => $statuses, 'value' => old('status', $location->status ?: 'active')])
        @include('partials.field', ['label' => 'Description', 'name' => 'description', 'as' => 'textarea', 'value' => old('description', $location->description), 'wide' => true])
    </div>
    <div class="form-actions">
        <button class="btn" type="submit">Save location</button>
        <a class="btn secondary" href="{{ route('locations.index') }}">Cancel</a>
    </div>
</form>
@endsection
