@extends('layouts.app')
@section('eyebrow', 'Plant')
@section('title', 'Equipment')
@section('actions')
    @if ($canManage)<a class="btn" href="{{ route('equipment.create') }}">Add equipment</a>@endif
@endsection

@section('content')
<div class="panel">
    <form class="filters" method="GET">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Name, code, registration">
        <select name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn small">Filter</button>
    </form>
    <table class="grid">
        <thead><tr><th>Equipment</th><th>Type</th><th>Project</th><th>Operator</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($equipment as $item)
            <tr>
                <td><a href="{{ route('equipment.show', $item) }}"><strong>{{ $item->name }}</strong></a><div class="mini">{{ $item->code }} · {{ $item->registration_number }}</div></td>
                <td>{{ $item->equipment_type }}</td>
                <td>{{ $item->project?->name ?? 'Unassigned' }}</td>
                <td>{{ $item->operator_name ?: '—' }}</td>
                <td>@include('partials.badge', ['status' => $item->status, 'label' => $item->statusLabel()])</td>
                <td class="right"><a href="{{ route('equipment.show', $item) }}">Usage</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No equipment yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $equipment->links() }}
</div>
@endsection
