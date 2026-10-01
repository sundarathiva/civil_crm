<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Athiva') · Athiva</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/civora.css') }}">
</head>
<body>
@php $role = auth()->user()->role?->slug; @endphp
<input id="nav-toggle" class="nav-toggle" type="checkbox">
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="mark">A</span>
            <span><strong>Athiva</strong><small>Construction CRM</small></span>
        </a>
        <nav class="nav">
            <a class="{{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
            @if ($role === 'worker')
                <a class="{{ request()->routeIs('my-work') ? 'is-active' : '' }}" href="{{ route('my-work') }}">My work</a>
            @else
                <p class="nav-label">Projects</p>
                <a class="{{ request()->routeIs('projects.*') ? 'is-active' : '' }}" href="{{ route('projects.index') }}">Projects</a>
                <a class="{{ request()->routeIs('locations.*') ? 'is-active' : '' }}" href="{{ route('locations.index') }}">Locations</a>
                <a class="{{ request()->is('works/pillars*') ? 'is-active' : '' }}" href="{{ route('works.index', 'pillars') }}">Pillars</a>
                <a class="{{ request()->is('works/walls*') ? 'is-active' : '' }}" href="{{ route('works.index', 'walls') }}">Walls</a>
                <a class="{{ request()->is('works/bridges*') ? 'is-active' : '' }}" href="{{ route('works.index', 'bridges') }}">Bridges</a>
                <p class="nav-label">Site</p>
                <a class="{{ request()->routeIs('materials.*') ? 'is-active' : '' }}" href="{{ route('materials.index') }}">Materials</a>
                <a class="{{ request()->routeIs('equipment.*') ? 'is-active' : '' }}" href="{{ route('equipment.index') }}">Equipment</a>
                <a class="{{ request()->routeIs('workers.*') ? 'is-active' : '' }}" href="{{ route('workers.index') }}">Workers</a>
                <a class="{{ request()->routeIs('daily-reports.*') ? 'is-active' : '' }}" href="{{ route('daily-reports.index') }}">Daily reports</a>
                <a class="{{ request()->routeIs('reports.*') ? 'is-active' : '' }}" href="{{ route('reports.index') }}">Reports</a>
                @if ($role === 'super_admin')
                    <p class="nav-label">Office</p>
                    <a class="{{ request()->routeIs('users.*') ? 'is-active' : '' }}" href="{{ route('users.index') }}">Users</a>
                @endif
            @endif
        </nav>
        <div class="user-card">
            <strong>{{ auth()->user()->name }}</strong>
            <span>{{ auth()->user()->roleName() }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout" type="submit">Sign out</button>
            </form>
        </div>
    </aside>
    <main class="main">
        <label class="burger btn secondary" for="nav-toggle">Menu</label>
        <header class="top">
            <div>
                <p class="eyebrow">@yield('eyebrow', 'Athiva')</p>
                <h1>@yield('title')</h1>
                @hasSection('sub')<p class="sub">@yield('sub')</p>@endif
            </div>
            <div>@yield('actions')</div>
        </header>
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash error">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
