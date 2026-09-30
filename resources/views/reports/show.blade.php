@extends('layouts.app')
@section('eyebrow', 'Report')
@section('title', $title)
@section('actions')
    <a class="btn secondary" href="{{ route('reports.index') }}">All reports</a>
    <button class="btn" type="button" onclick="window.print()">Print</button>
@endsection

@section('content')
<div class="panel">
    <table class="grid">
        <thead>
            <tr>@foreach ($columns as $column)<th>{{ $column }}</th>@endforeach</tr>
        </thead>
        <tbody>
        @forelse ($rows as $row)
            <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
        @empty
            <tr><td class="empty" colspan="{{ count($columns) }}">Nothing to show.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
