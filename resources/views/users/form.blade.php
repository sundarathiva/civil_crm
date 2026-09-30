@extends('layouts.app')
@section('title', $user->exists ? 'Edit user' : 'New user')

@section('content')
<form class="panel" method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
    @csrf
    @if ($user->exists) @method('PUT') @endif
    <div class="form-grid">
        @include('partials.field', ['label' => 'Name', 'name' => 'name', 'value' => old('name', $user->name), 'required' => true])
        @include('partials.field', ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'value' => old('email', $user->email), 'required' => true])
        @include('partials.field', ['label' => 'Phone', 'name' => 'phone', 'value' => old('phone', $user->phone)])
        @include('partials.field', ['label' => 'Role', 'name' => 'role_id', 'as' => 'select', 'options' => $roles->pluck('name', 'id'), 'value' => old('role_id', $user->role_id), 'placeholder' => 'Select role', 'required' => true])
        @include('partials.field', ['label' => 'Status', 'name' => 'status', 'as' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'value' => old('status', $user->status ?: 'active')])
        @include('partials.field', ['label' => $user->exists ? 'New password' : 'Password', 'name' => 'password', 'type' => 'password', 'required' => !$user->exists])
    </div>
    <div class="form-actions">
        <button class="btn">Save user</button>
        <a class="btn secondary" href="{{ route('users.index') }}">Cancel</a>
    </div>
</form>
@endsection
