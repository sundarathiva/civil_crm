@extends('layouts.app')
@section('eyebrow', 'Portfolio')
@section('title', 'Projects')
@section('sub', 'Every civil package, from new to completed.')
@section('actions')
    @if ($canCreate)
        <a class="btn" href="{{ route('projects.create') }}">New project</a>
    @endif
@endsection

@section('content')
<div class="panel">
    <form class="filters" method="GET">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, code, client">
        <select name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn small" type="submit">Filter</button>
    </form>
    <table class="grid">
        <thead>
            <tr><th>Code</th><th>Project</th><th>Type</th><th>Client</th><th>Status</th><th>Progress</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($projects as $project)
            <tr>
                <td>{{ $project->code }}</td>
                <td><a href="{{ route('projects.show', $project) }}"><strong>{{ $project->name }}</strong></a><div class="mini">{{ $project->location_summary }}</div></td>
                <td>{{ $project->type?->name }}</td>
                <td>{{ $project->client_name }}</td>
                <td>@include('partials.badge', ['status' => $project->status, 'label' => $project->statusLabel()])</td>
                <td style="min-width:120px"><div class="progress"><i style="width:{{ $project->progress }}%"></i></div><small>{{ $project->progress }}%</small></td>
                <td class="right"><a class="btn small secondary" href="{{ route('projects.show', $project) }}">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No projects match.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $projects->links() }}
</div>
@endsection
