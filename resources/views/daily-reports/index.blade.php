@extends('layouts.app')
@section('eyebrow', 'Daily site reports')
@section('title', 'Reports')
@section('actions')
    @if ($canCreate)
        <a class="btn" href="{{ route('daily-reports.create') }}">New report</a>
    @endif
@endsection

@section('content')
<div class="panel">
    <form class="filters" method="GET">
        <select name="project_id">
            <option value="">All projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ request('date') }}">
        <button class="btn small">Filter</button>
    </form>
    <table class="grid">
        <thead><tr><th>Report</th><th>Date</th><th>Project</th><th>Progress</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($reports as $report)
            <tr>
                <td><a href="{{ route('daily-reports.show', $report) }}"><strong>{{ $report->code }}</strong></a><div class="mini">{{ $report->siteEngineer?->name }}</div></td>
                <td>{{ $report->report_date->format('d M Y') }}</td>
                <td>{{ $report->project->name }}<div class="mini">{{ $report->location->name }}</div></td>
                <td>{{ $report->progress }}%</td>
                <td>@include('partials.badge', ['status' => $report->status, 'label' => $report->statusLabel()])</td>
                <td class="right"><a href="{{ route('daily-reports.show', $report) }}">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No daily reports yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $reports->links() }}
</div>
@endsection
