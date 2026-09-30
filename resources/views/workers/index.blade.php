@extends('layouts.app')
@section('eyebrow', 'Crew')
@section('title', 'Workers')
@section('actions')
    <a class="btn secondary" href="{{ route('workers.attendance') }}">Attendance</a>
    @if ($canManage)<a class="btn" href="{{ route('workers.create') }}">Add worker</a>@endif
@endsection

@section('content')
<div class="panel">
    <form class="filters" method="GET">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Name, code, mobile">
        <select name="worker_type">
            <option value="">All types</option>
            @foreach ($types as $key => $label)
                <option value="{{ $key }}" @selected(request('worker_type') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn small">Filter</button>
    </form>
    <table class="grid">
        <thead><tr><th>Worker</th><th>Type</th><th>Project</th><th>Wage</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($workers as $worker)
            <tr>
                <td><a href="{{ route('workers.show', $worker) }}"><strong>{{ $worker->name }}</strong></a><div class="mini">{{ $worker->code }} · {{ $worker->mobile }}</div></td>
                <td>{{ $worker->worker_type }}<div class="mini">{{ $worker->skill }}</div></td>
                <td>{{ $worker->project?->name ?? '—' }}<div class="mini">{{ $worker->location?->name }}</div></td>
                <td>{{ number_format($worker->daily_wage, 2) }}</td>
                <td>@include('partials.badge', ['status' => $worker->status, 'label' => ucfirst($worker->status)])</td>
                <td class="right"><a href="{{ route('workers.show', $worker) }}">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No workers yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $workers->links() }}
</div>
@endsection
