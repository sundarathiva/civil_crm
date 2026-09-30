@extends('layouts.app')
@section('eyebrow', 'On site')
@section('title', 'My work')
@section('sub', 'Assigned tasks, location, and attendance.')

@section('content')
@if (!$worker)
    <div class="panel"><p>Your login is not linked to a worker profile yet. Ask a super admin to connect it.</p></div>
@else
    <section class="stats">
        <article class="stat"><span>Worker</span><strong style="font-size:28px">{{ $worker->code }}</strong><em>{{ $worker->worker_type }}</em></article>
        <article class="stat"><span>Project</span><strong style="font-size:28px">{{ $worker->project?->name ?? '—' }}</strong></article>
        <article class="stat"><span>Location</span><strong style="font-size:28px">{{ $worker->location?->name ?? '—' }}</strong></article>
        <article class="stat"><span>Today</span><strong style="font-size:28px">{{ $today ? $today->statusLabel() : 'Not marked' }}</strong></article>
    </section>
    <section class="layout-split">
        <div class="panel">
            <h2>Assigned tasks</h2>
            @forelse ($assignments as $assignment)
                <article class="work-card" style="margin-bottom:10px">
                    <strong>{{ $assignment->workLabel() }}</strong>
                    <div class="mini">
                        <span>{{ $assignment->project->name }}</span>
                        <span>{{ $assignment->location?->name ?? 'All locations' }}</span>
                        <span>Since {{ $assignment->assigned_on->format('d M Y') }}</span>
                    </div>
                </article>
            @empty
                <p class="empty">No active assignment.</p>
            @endforelse
        </div>
        <div class="panel">
            <h2>Attendance</h2>
            <table class="grid">
                <thead><tr><th>Date</th><th>Status</th><th>Site</th></tr></thead>
                <tbody>
                @forelse ($attendance as $row)
                    <tr>
                        <td>{{ $row->attended_on->format('d M') }}</td>
                        <td>@include('partials.badge', ['status' => $row->status, 'label' => $row->statusLabel()])</td>
                        <td>{{ $row->location?->name ?? $row->project?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty">No attendance yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection
