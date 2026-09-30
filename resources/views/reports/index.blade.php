@extends('layouts.app')
@section('eyebrow', 'Ledgers')
@section('title', 'Reports')
@section('sub', 'Project, location, stock, plant, crew, and daily site reports.')

@section('content')
@foreach ($groups as $group => $links)
    <div class="panel" style="margin-bottom:14px">
        <h2>{{ $group }}</h2>
        <div class="cards">
            @foreach ($links as $key => $label)
                <a class="report-card" href="{{ route('reports.show', $key) }}">
                    <strong>{{ $label }}</strong>
                    <small>Open report</small>
                </a>
            @endforeach
        </div>
    </div>
@endforeach
@endsection
