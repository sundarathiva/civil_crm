@extends('layouts.app')
@section('title', $material->exists ? 'Edit '.$material->name : 'New material')

@section('content')
<form class="panel" method="POST" action="{{ $material->exists ? route('materials.update', $material) : route('materials.store') }}">
    @csrf
    @if ($material->exists) @method('PUT') @endif
    <div class="form-grid">
        @include('partials.field', ['label' => 'Name', 'name' => 'name', 'value' => old('name', $material->name), 'required' => true])
        @include('partials.field', ['label' => 'Code', 'name' => 'code', 'value' => old('code', $material->code), 'required' => true])
        @include('partials.field', ['label' => 'Category', 'name' => 'category', 'as' => 'select', 'options' => $categories, 'value' => old('category', $material->category), 'placeholder' => 'Select', 'required' => true])
        @include('partials.field', ['label' => 'Unit', 'name' => 'unit', 'value' => old('unit', $material->unit), 'required' => true])
        @include('partials.field', ['label' => 'Minimum stock', 'name' => 'minimum_stock', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'value' => old('minimum_stock', $material->minimum_stock ?? 0), 'required' => true])
        @include('partials.field', ['label' => 'Status', 'name' => 'status', 'as' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'value' => old('status', $material->status ?: 'active')])
    </div>
    <div class="form-actions">
        <button class="btn">Save material</button>
        <a class="btn secondary" href="{{ route('materials.index') }}">Cancel</a>
    </div>
</form>
@endsection
