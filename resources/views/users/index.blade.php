@extends('layouts.app')
@section('eyebrow', 'Office')
@section('title', 'Users')
@section('actions')
    <a class="btn" href="{{ route('users.create') }}">New user</a>
@endsection

@section('content')
<div class="panel">
    <form class="filters" method="GET">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Name or email">
        <select name="role">
            <option value="">All roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->slug }}" @selected(request('role') === $role->slug)>{{ $role->name }}</option>
            @endforeach
        </select>
        <button class="btn small">Filter</button>
    </form>
    <table class="grid">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td><strong>{{ $user->name }}</strong><div class="mini">{{ $user->phone }}</div></td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->role?->name }}</td>
                <td>@include('partials.badge', ['status' => $user->status, 'label' => ucfirst($user->status)])</td>
                <td class="actions">
                    <a class="btn small secondary" href="{{ route('users.edit', $user) }}">Edit</a>
                    @if (!$user->is(auth()->user()))
                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Remove this user?')">
                            @csrf @method('DELETE')
                            <button class="btn small danger">Delete</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $users->links() }}
</div>
@endsection
