@extends('layouts.app')
@section('eyebrow', $material->code)
@section('title', $material->name)
@section('sub', $material->category.' · '.$material->unit)
@section('actions')
    @if ($canManage)
        <a class="btn secondary" href="{{ route('materials.edit', $material) }}">Edit</a>
    @endif
@endsection

@section('content')
<section class="stats">
    <article class="stat"><span>Opening</span><strong>{{ $material->stock->opening_qty ?? '0.00' }}</strong></article>
    <article class="stat"><span>Received</span><strong>{{ $material->stock->received_qty ?? '0.00' }}</strong></article>
    <article class="stat"><span>Issued / used</span><strong>{{ number_format((float) ($material->stock->issued_qty ?? 0) + (float) ($material->stock->used_qty ?? 0), 2) }}</strong></article>
    <article class="stat"><span>Current</span><strong>{{ $material->current_stock }}</strong><em>{{ $material->isLow() ? 'Low stock' : 'Above minimum '.$material->minimum_stock }}</em></article>
</section>
<section class="layout-split">
    <div class="panel">
        <h2>Ledger</h2>
        <table class="grid">
            <thead><tr><th>Date</th><th>Movement</th><th>Qty</th><th>Project</th><th>Note</th></tr></thead>
            <tbody>
            @forelse ($transactions as $txn)
                <tr>
                    <td>{{ $txn->transacted_on->format('d M Y') }}</td>
                    <td>{{ $txn->typeLabel() }}</td>
                    <td>{{ $txn->quantity }}</td>
                    <td>{{ $txn->project?->code ?? 'Store' }}</td>
                    <td>{{ $txn->remarks }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No movements yet. Record opening stock to start the balance.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $transactions->links() }}
    </div>
    <form class="panel" method="POST" action="{{ route('materials.transact', $material) }}">
        @csrf
        <h2>Record movement</h2>
        <div class="form-grid" style="grid-template-columns:1fr">
            @include('partials.field', ['label' => 'Type', 'name' => 'type', 'as' => 'select', 'options' => $types, 'value' => old('type'), 'required' => true])
            @include('partials.field', ['label' => 'Quantity', 'name' => 'quantity', 'type' => 'number', 'step' => '0.01', 'min' => '0.01', 'value' => old('quantity'), 'required' => true])
            @include('partials.field', ['label' => 'Date', 'name' => 'transacted_on', 'type' => 'date', 'value' => old('transacted_on', now()->toDateString()), 'required' => true])
            @include('partials.field', ['label' => 'Project', 'name' => 'project_id', 'as' => 'select', 'id' => 'stock_project', 'options' => $projects->pluck('name', 'id'), 'value' => old('project_id'), 'placeholder' => 'Store / optional'])
            @include('partials.field', ['label' => 'Location', 'name' => 'project_location_id', 'as' => 'select', 'id' => 'stock_location', 'options' => []])
            @include('partials.field', ['label' => 'Remarks', 'name' => 'remarks', 'value' => old('remarks')])
        </div>
        <div class="form-actions"><button class="btn">Update stock</button></div>
    </form>
</section>
@php
    $catalog = $projects->map(fn ($project) => [
        'id' => $project->id,
        'locations' => $project->locations->map(fn ($location) => ['id' => $location->id, 'name' => $location->name])->values(),
    ])->values();
@endphp
<script>
const catalog = @json($catalog);
const projectSelect = document.getElementById('stock_project');
const locationSelect = document.getElementById('stock_location');
function fill() {
    const project = catalog.find(item => String(item.id) === projectSelect.value);
    locationSelect.innerHTML = '<option value="">Optional</option>';
    (project?.locations || []).forEach(location => {
        const option = document.createElement('option');
        option.value = location.id;
        option.textContent = location.name;
        locationSelect.appendChild(option);
    });
}
projectSelect?.addEventListener('change', fill);
fill();
</script>
@endsection
