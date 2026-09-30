@extends('layouts.app')
@section('eyebrow', $project->code)
@section('title', $project->name)
@section('sub', $project->client_name.' · '.$project->type?->name)
@section('actions')
    @if ($canEdit)
        <a class="btn secondary" href="{{ route('projects.edit', $project) }}">Edit</a>
    @endif
    @if ($canSubmit)
        <a class="btn" href="{{ route('daily-reports.create', ['project_id' => $project->id]) }}">Daily report</a>
    @endif
@endsection

@section('content')
<section class="stats">
    <article class="stat"><span>Status</span><strong style="font-size:28px">{{ $project->statusLabel() }}</strong></article>
    <article class="stat"><span>Overall</span><strong>{{ $project->progress }}%</strong></article>
    <article class="stat"><span>Engineer</span><strong style="font-size:24px">{{ $project->engineer?->name ?? '—' }}</strong></article>
    <article class="stat"><span>Site engineer</span><strong style="font-size:24px">{{ $project->siteEngineer?->name ?? '—' }}</strong></article>
</section>
<div class="panel" style="margin-bottom:14px">
    <div class="progress"><i style="width: {{ $project->progress }}%"></i></div>
    <div class="mini">
        <span>Pillars {{ $latest->pillar_progress ?? 0 }}%</span>
        <span>Walls {{ $latest->wall_progress ?? 0 }}%</span>
        <span>Bridges {{ $latest->bridge_progress ?? 0 }}%</span>
        <span>{{ optional($project->start_date)->format('d M Y') }} → {{ optional($project->expected_completion_date)->format('d M Y') }}</span>
    </div>
    @if ($project->description)<p style="margin-top:12px">{{ $project->description }}</p>@endif
</div>
<section class="layout-split">
    <div>
        <div class="panel" style="margin-bottom:14px">
            <div class="panel-head"><h2>Locations</h2>@if($canEdit)<a class="btn small" href="{{ route('locations.create', ['project_id' => $project->id]) }}">Add</a>@endif</div>
            @forelse ($project->locations as $location)
                <article class="work-card" style="margin-bottom:8px">
                    <strong>{{ $location->name }}</strong>
                    <div class="mini"><span>{{ $location->chainage ?: 'No chainage' }}</span><span>{{ $location->siteEngineer?->name ?? 'No site engineer' }}</span></div>
                </article>
            @empty
                <p class="empty">Add a location before site work starts.</p>
            @endforelse
        </div>
        @foreach (['pillars' => 'Pillars', 'walls' => 'Walls', 'bridges' => 'Bridges'] as $relation => $label)
            <div class="panel" style="margin-bottom:14px">
                <div class="panel-head">
                    <h2>{{ $label }}</h2>
                    @if ($canEdit)<a class="btn small" href="{{ route('works.create', [$relation, 'project_id' => $project->id]) }}">Add</a>@endif
                </div>
                <table class="grid">
                    <tbody>
                    @forelse ($project->{$relation} as $item)
                        <tr>
                            <td><strong>{{ $item->code }}</strong><div class="mini">{{ $item->location?->name }}</div></td>
                            <td>@include('partials.badge', ['status' => $item->status, 'label' => $item->statusLabel()])</td>
                            <td style="width:140px"><div class="progress"><i style="width:{{ $item->progress }}%"></i></div></td>
                            <td class="right"><a href="{{ route('works.edit', [$relation, $item->id]) }}">Update</a></td>
                        </tr>
                    @empty
                        <tr><td class="empty">None yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
    <div>
        <div class="panel" style="margin-bottom:14px">
            <h2>Status path</h2>
            @foreach ($project->statusHistory as $event)
                <p style="margin-bottom:8px"><strong>{{ \App\Support\Options::label(\App\Support\Options::PROJECT_STATUSES, $event->to_status) }}</strong><br><span class="mini">{{ $event->created_at->format('d M Y') }} · {{ $event->user?->name }}</span></p>
            @endforeach
        </div>
        <div class="panel">
            <h2>Recent reports</h2>
            @forelse ($project->reports->sortByDesc('report_date')->take(5) as $report)
                <a class="work-card" style="margin-bottom:8px" href="{{ route('daily-reports.show', $report) }}">
                    <strong>{{ $report->code }}</strong>
                    <div class="mini"><span>{{ $report->report_date->format('d M Y') }}</span><span>{{ $report->statusLabel() }}</span></div>
                </a>
            @empty
                <p class="empty">No daily reports yet.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
