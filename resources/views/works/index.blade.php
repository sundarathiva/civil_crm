@extends('layouts.app')
@section('eyebrow', 'Structures')
@section('title', $meta['plural'])
@section('actions')
    @if ($canCreate)
        <a class="btn" href="{{ route('works.create', $type) }}">Add {{ strtolower($meta['label']) }}</a>
    @endif
@endsection

@section('content')
<div class="panel">
    <form class="filters" method="GET">
        <select name="project_id">
            <option value="">All projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn small">Filter</button>
    </form>
    <table class="grid">
        <thead>
            <tr>
                <th>Code</th><th>Project</th><th>Location</th>
                @if ($type === 'walls')<th>Size</th>@endif
                @if ($type === 'bridges')<th>Span</th>@endif
                <th>Status</th><th>Progress</th><th></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($items as $item)
            <tr>
                <td><strong>{{ $item->code }}</strong></td>
                <td>{{ $item->project->name }}</td>
                <td>{{ $item->location->name }}</td>
                @if ($type === 'walls')<td>{{ $item->length }} m × {{ $item->height }} m</td>@endif
                @if ($type === 'bridges')<td>{{ $item->bridge_length }} m × {{ $item->bridge_width }} m</td>@endif
                <td>@include('partials.badge', ['status' => $item->status, 'label' => $item->statusLabel()])</td>
                <td style="min-width:110px"><div class="progress"><i style="width:{{ $item->progress }}%"></i></div></td>
                <td class="right"><a href="{{ route('works.edit', [$type, $item->id]) }}">Edit</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No {{ strtolower($meta['plural']) }} yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $items->links() }}
</div>
@endsection
