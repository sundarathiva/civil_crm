@extends('layouts.app')
@section('eyebrow', 'Civil Construction CRM')
@section('title', 'Site office')
@section('sub', 'Projects, plant, and the work happening today.')

@section('content')
<section class="stats">
    <article class="stat"><span>Projects</span><strong>{{ $stats['projects'] }}</strong></article>
    <article class="stat"><span>Active</span><strong>{{ $stats['active'] }}</strong></article>
    <article class="stat"><span>Completed</span><strong>{{ $stats['completed'] }}</strong></article>
    <article class="stat"><span>Progress</span><strong>{{ $stats['progress'] }}%</strong></article>
</section>
<section class="stats">
    <article class="stat"><span>Pillars</span><strong>{{ $stats['pillars'] }}</strong></article>
    <article class="stat"><span>Walls</span><strong>{{ number_format($stats['wall_length'], 0) }}<em> m</em></strong></article>
    <article class="stat"><span>Bridges</span><strong>{{ $stats['bridges'] }}</strong></article>
    <article class="stat"><span>Workers</span><strong>{{ $stats['workers'] }}</strong></article>
</section>
<section class="layout-split">
    <div class="panel">
        <h2>Project progress</h2>
        @forelse ($portfolio as $project)
            <div style="margin-bottom: 16px;">
                <div class="progress-row">
                    <strong>{{ $project->name }}</strong>
                    <div class="progress"><i style="width: {{ $project->progress }}%"></i></div>
                    <span>{{ $project->progress }}%</span>
                </div>
                <div class="mini">
                    <span>Pillars {{ $project->pillar_progress }}%</span>
                    <span>Walls {{ $project->wall_progress }}%</span>
                    <span>Bridges {{ $project->bridge_progress }}%</span>
                </div>
            </div>
        @empty
            <p class="empty">No projects yet.</p>
        @endforelse
    </div>
    <div class="panel">
        <h2>Needs attention</h2>
        <div class="alert-list">
            <div class="alert-item"><span>Pending daily reports</span><strong>{{ $stats['pending_reports'] }}</strong></div>
            <div class="alert-item"><span>Low stock materials</span><strong>{{ $stats['low_stock'] }}</strong></div>
            <div class="alert-item"><span>Equipment under maintenance</span><strong>{{ $stats['maintenance'] }}</strong></div>
        </div>
        <h2 style="margin-top:22px">Waiting for review</h2>
        @forelse ($queue as $report)
            <a class="work-card" href="{{ route('daily-reports.show', $report) }}" style="margin-top:8px">
                <strong>{{ $report->code }}</strong>
                <small>{{ $report->project->name }} · {{ $report->location->name }}</small>
            </a>
        @empty
            <p class="empty">No reports are waiting.</p>
        @endforelse
    </div>
</section>
@endsection
