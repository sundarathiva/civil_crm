@extends('layouts.app')
@section('eyebrow', $report->report_date->format('d M Y'))
@section('title', $report->code)
@section('sub', $report->project->name.' · '.$report->location->name)
@section('actions')
    @if ($canEdit)
        <a class="btn" href="{{ route('daily-reports.edit', $report) }}">Correct and resubmit</a>
    @endif
@endsection

@section('content')
<section class="stats">
    <article class="stat"><span>Status</span><strong style="font-size:28px">{{ $report->statusLabel() }}</strong></article>
    <article class="stat"><span>Progress</span><strong>{{ $report->progress }}%</strong></article>
    <article class="stat"><span>Workers</span><strong>{{ $report->worker_count }}</strong></article>
    <article class="stat"><span>Work</span><strong style="font-size:24px">{{ $report->workLabel() }}</strong></article>
</section>
<section class="layout-split">
    <div class="panel">
        <h2>Site notes</h2>
        <p>{{ $report->work_description }}</p>
        @if ($report->issues)<p style="margin-top:12px"><strong>Issues.</strong> {{ $report->issues }}</p>@endif
        @if ($report->remarks)<p style="margin-top:12px"><strong>Remarks.</strong> {{ $report->remarks }}</p>@endif
        @if ($report->review_note)
            <div class="flash {{ $report->status === 'rejected' ? 'error' : '' }}" style="margin-top:16px">
                Review by {{ $report->reviewer?->name }}: {{ $report->review_note }}
            </div>
        @endif
        <h2 style="margin-top:22px">Materials</h2>
        <table class="grid">
            <tbody>
            @forelse ($report->materials as $line)
                <tr><td>{{ $line->material->name }}</td><td>{{ $line->quantity }} {{ $line->material->unit }}</td><td>{{ $line->posted ? 'Posted to stock' : 'Pending approval' }}</td></tr>
            @empty
                <tr><td class="empty">No materials recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
        <h2 style="margin-top:22px">Equipment</h2>
        <table class="grid">
            <tbody>
            @forelse ($report->equipmentLines as $line)
                <tr><td>{{ $line->equipment->code }}</td><td>{{ $line->working_hours ? $line->working_hours.' h' : '—' }}</td><td>{{ $line->fuel_used ? $line->fuel_used.' litres' : '—' }}</td></tr>
            @empty
                <tr><td class="empty">No equipment recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
        <h2 style="margin-top:22px">Crew</h2>
        <table class="grid">
            <tbody>
            @forelse ($report->workers as $line)
                <tr><td>{{ $line->worker->name }}</td><td>{{ $line->statusLabel() }}</td></tr>
            @empty
                <tr><td class="empty">No workers listed.</td></tr>
            @endforelse
            </tbody>
        </table>
        @if ($report->photos->isNotEmpty())
            <h2 style="margin-top:22px">Photos</h2>
            <div class="photos">
                @foreach ($report->photos as $photo)
                    <a href="{{ $photo->url() }}" target="_blank"><img src="{{ $photo->url() }}" alt="{{ $photo->original_name }}"></a>
                @endforeach
            </div>
        @endif
    </div>
    <div class="panel">
        <h2>Review</h2>
        <p class="mini">Site engineer {{ $report->siteEngineer?->name ?? '—' }}</p>
        @if ($canReview && $report->status === 'submitted')
            <form method="POST" action="{{ route('daily-reports.review', $report) }}" style="margin-top:14px">
                @csrf
                @include('partials.field', ['label' => 'Note', 'name' => 'review_note', 'as' => 'textarea', 'value' => old('review_note'), 'wide' => true])
                <div class="form-actions">
                    <button class="btn" name="decision" value="approved">Approve</button>
                    <button class="btn danger" name="decision" value="rejected">Reject</button>
                </div>
            </form>
        @elseif ($report->status === 'approved')
            <p style="margin-top:12px">Approved {{ optional($report->reviewed_at)->format('d M Y H:i') }}. Material usage has been posted to stock, and linked work progress was updated.</p>
        @elseif ($report->status === 'rejected')
            <p style="margin-top:12px">Sent back for correction. The site engineer can edit and submit again.</p>
        @else
            <p style="margin-top:12px">This report is still a draft.</p>
        @endif
    </div>
</section>
@endsection
