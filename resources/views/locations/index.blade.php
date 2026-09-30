@extends('layouts.app')
@section('eyebrow', 'Ground')
@section('title', 'Locations')
@section('actions')
    @if (auth()->user()->hasRole('super_admin', 'engineer'))
        <a class="btn" href="{{ route('locations.create', request()->only('project_id')) }}">Add location</a>
    @endif
@endsection

@section('content')
<div class="panel">
    <form class="filters" method="GET">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Name, chainage, address">
        <select name="project_id">
            <option value="">All projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
        <button class="btn small">Filter</button>
    </form>
    <table class="grid">
        <thead><tr><th>Location</th><th>Project</th><th>Chainage</th><th>Site engineer</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($locations as $location)
            <tr>
                <td><strong>{{ $location->name }}</strong><div class="mini">{{ $location->address }}</div></td>
                <td>{{ $location->project->code }}</td>
                <td>{{ $location->chainage ?: '—' }}</td>
                <td>{{ $location->siteEngineer?->name ?? '—' }}</td>
                <td>@include('partials.badge', ['status' => $location->status, 'label' => $location->statusLabel()])</td>
                <td class="right"><a href="{{ route('locations.edit', $location) }}">Edit</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No locations yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $locations->links() }}
</div>
@endsection
