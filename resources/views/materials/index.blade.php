@extends('layouts.app')
@section('eyebrow', 'Store')
@section('title', 'Materials')
@section('sub', 'Current stock = opening + received + returned − issued − used.')
@section('actions')
    @if ($canManage)
        <a class="btn" href="{{ route('materials.create') }}">Add material</a>
    @endif
@endsection

@section('content')
<div class="panel">
    <form class="filters" method="GET">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Name or code">
        <label class="mini"><input type="checkbox" name="low" value="1" @checked(request('low'))> Low stock only</label>
        <button class="btn small">Filter</button>
    </form>
    <table class="grid">
        <thead><tr><th>Material</th><th>Category</th><th>Unit</th><th>Current</th><th>Minimum</th><th>Alert</th><th></th></tr></thead>
        <tbody>
        @forelse ($materials as $material)
            <tr>
                <td><a href="{{ route('materials.show', $material) }}"><strong>{{ $material->name }}</strong></a><div class="mini">{{ $material->code }}</div></td>
                <td>{{ $material->category }}</td>
                <td>{{ $material->unit }}</td>
                <td>{{ $material->current_stock }}</td>
                <td>{{ $material->minimum_stock }}</td>
                <td>@if($material->isLow())<span class="badge" data-status="rejected">Low</span>@else<span class="badge" data-status="active">OK</span>@endif</td>
                <td class="right"><a href="{{ route('materials.show', $material) }}">Ledger</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No materials yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $materials->links() }}
</div>
@endsection
