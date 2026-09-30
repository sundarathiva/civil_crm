@extends('layouts.guest')

@section('content')
<div class="guest">
    <section class="guest-hero">
        <div class="brand">
            <span class="mark">A</span>
            <span><strong>Athiva</strong><small>Construction CRM</small></span>
        </div>
        <div>
            <p class="eyebrow" style="color:#e2a23a">Site office</p>
            <h1>From the first pier to the last daily report.</h1>
            <p class="sub" style="color:#d5e0d8; max-width: 460px; margin-top: 16px;">Projects, locations, materials, plant, and the people on the ground — one ledger for civil work.</p>
        </div>
        <p>Pillars · Walls · Bridges</p>
    </section>
    <section class="guest-form">
        <form class="login-card" method="POST" action="{{ route('login.store') }}">
            @csrf
            <p class="eyebrow">Welcome back</p>
            <h1 style="font-size:42px; margin: 8px 0 18px;">Sign in</h1>
            @include('partials.field', ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'value' => old('email'), 'required' => true, 'wide' => true])
            <div style="height:12px"></div>
            @include('partials.field', ['label' => 'Password', 'name' => 'password', 'type' => 'password', 'required' => true, 'wide' => true])
            <label style="display:flex; gap:8px; margin:14px 0; color:var(--muted);">
                <input type="checkbox" name="remember" value="1"> Keep me signed in
            </label>
            <button class="btn gold" type="submit">Enter the site office</button>
        </form>
    </section>
</div>
@endsection
