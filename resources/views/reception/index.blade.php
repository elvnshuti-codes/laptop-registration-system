@extends('layouts.app')

@section('title', 'Reception')

@section('content')
@php
    $toServe = $rows->whereIn('stage', ['waiting', 'progress']);
    $served = $rows->where('stage', 'served');
    $stageBadge = fn ($s) => match ($s) {
        'entry' => ['bg-secondary', 'At Entry'],
        'waiting' => ['bg-warning text-dark', 'Waiting'],
        'progress' => ['bg-info text-dark', 'In progress'],
        default => ['bg-success', 'Served'],
    };
    $waitClass = fn ($r) => $r['late'] ? 'bg-danger' : ($r['warn'] ? 'bg-warning text-dark' : 'bg-light text-dark border');
@endphp

{{-- Scan + shortcuts --}}
<div class="row g-2 align-items-start mb-3">
    <div class="col">
        <form method="GET" action="{{ route('reception.lookup') }}">
            <div class="input-group input-group-lg">
                <input type="text" name="code" value="{{ old('code') }}"
                       class="form-control @error('code') is-invalid @enderror"
                       placeholder="Scan the visitor's ticket or type the visit code" autocomplete="off" autofocus required>
                <button type="submit" class="btn btn-primary">Find visitor</button>
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </form>
    </div>
    <div class="col-auto d-flex gap-2">
        <a href="{{ route('reception.bookings.index') }}" class="btn btn-outline-primary btn-lg">Bookings</a>
        <a href="{{ route('reception.bookings.create') }}" class="btn btn-warning btn-lg">Pre-book</a>
    </div>
</div>

@if ($unserved->isNotEmpty())
    <div class="alert alert-danger d-flex justify-content-between align-items-center py-2">
        <div><strong>{{ $unserved->count() }}</strong> visitor(s) left without being served and need to be acknowledged.</div>
        <a href="#" id="goto-followup" class="btn btn-sm btn-danger">Review</a>
    </div>
@endif

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3 align-items-end" id="recTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" id="tab-toserve" data-bs-toggle="tab" data-bs-target="#pane-toserve" type="button">
            To serve <span class="badge bg-warning text-dark">{{ $toServe->count() }}</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="tab-served" data-bs-toggle="tab" data-bs-target="#pane-served" type="button">
            Served <span class="badge bg-success">{{ $served->count() }}</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="tab-followup" data-bs-toggle="tab" data-bs-target="#pane-followup" type="button">
            Follow-up <span class="badge {{ $unserved->isNotEmpty() ? 'bg-danger' : 'bg-secondary' }}">{{ $unserved->count() }}</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="tab-all" data-bs-toggle="tab" data-bs-target="#pane-all" type="button">
            All on site <span class="badge bg-secondary">{{ $rows->count() }}</span>
        </button>
    </li>
    <li class="nav-item ms-auto pb-1">
        <input type="search" id="rec-search" class="form-control form-control-sm" placeholder="Search the lists..." style="min-width: 14rem;">
    </li>
</ul>

<div class="tab-content">

    {{-- To serve --}}
    <div class="tab-pane fade show active" id="pane-toserve">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Purpose</th>
                                <th>Waiting</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($toServe as $r)
                                @php $v = $r['visit']; [$cls, $label] = $stageBadge($r['stage']); @endphp
                                <tr class="js-row">
                                    <td>
                                        <div class="fw-semibold">{{ $v->name }}</div>
                                        <div class="text-muted small font-monospace">{{ $v->visit_code }}</div>
                                    </td>
                                    <td>{{ $v->purpose }}</td>
                                    <td>
                                        <span class="badge {{ $waitClass($r) }}">
                                            {{ $r['sameDay'] ? $r['wait'] : 'Since ' . $r['checkIn']->format('d M') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $cls }}">{{ $label }}</span>
                                        @if ($r['destination'])
                                            <div class="small text-muted">{{ $r['destination'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('reception.visits.show', $v) }}" class="btn btn-primary">Serve</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Nobody is waiting to be served.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Served --}}
    <div class="tab-pane fade" id="pane-served">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Purpose</th>
                                <th>Destination</th>
                                <th>Served at</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($served as $r)
                                @php $v = $r['visit']; @endphp
                                <tr class="js-row">
                                    <td>
                                        <div class="fw-semibold">{{ $v->name }}</div>
                                        <div class="text-muted small font-monospace">{{ $v->visit_code }}</div>
                                    </td>
                                    <td>{{ $v->purpose }}</td>
                                    <td>{{ $r['destination'] ?: '—' }}</td>
                                    <td>{{ $r['servedAt'] ? $r['servedAt']->format('d M, H:i') : '—' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('reception.visits.show', $v) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No served visitors on site.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Follow-up: left without being served --}}
    <div class="tab-pane fade" id="pane-followup">
        @if ($unserved->isEmpty())
            <div class="card shadow-sm border-0">
                <div class="card-body text-muted text-center py-4">Nothing to follow up. Acknowledged records stay in Records, under "Left without being served".</div>
            </div>
        @else
            @include('partials._unserved', ['unserved' => $unserved, 'ackRoute' => 'reception.exceptions.acknowledge'])
        @endif
    </div>

    {{-- Everyone on site --}}
    <div class="tab-pane fade" id="pane-all">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Purpose</th>
                                <th>Checked in</th>
                                <th>Stage</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                @php $v = $r['visit']; [$cls, $label] = $stageBadge($r['stage']); @endphp
                                <tr class="js-row">
                                    <td>
                                        <div class="fw-semibold">{{ $v->name }}</div>
                                        <div class="text-muted small font-monospace">{{ $v->visit_code }}</div>
                                    </td>
                                    <td>{{ $v->purpose }}</td>
                                    <td>{{ $r['checkIn']->format('d M, H:i') }}</td>
                                    <td><span class="badge {{ $cls }}">{{ $label }}</span></td>
                                    <td class="text-end">
                                        @if ($r['stage'] === 'entry')
                                            <span class="text-muted small">Not through Entry yet</span>
                                        @else
                                            <a href="{{ route('reception.visits.show', $v) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No visitors are currently on site.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    $(function () {
        // The search box filters the rows of every list.
        $('#rec-search').on('input', function () {
            const text = this.value.toLowerCase();
            $('.js-row').each(function () {
                $(this).toggle($(this).text().toLowerCase().includes(text));
            });
        });

        // Remember which tab was open (a refresh or an Acknowledge returns to it).
        try {
            const saved = localStorage.getItem('recTab');
            if (saved && document.querySelector(saved)) {
                bootstrap.Tab.getOrCreateInstance(document.querySelector(saved)).show();
            }
        } catch (e) {}

        document.querySelectorAll('#recTabs button[data-bs-toggle="tab"]').forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', function () {
                try { localStorage.setItem('recTab', '#' + btn.id); } catch (e) {}
            });
        });

        // The red banner's Review button opens the Follow-up tab.
        $('#goto-followup').on('click', function (e) {
            e.preventDefault();
            bootstrap.Tab.getOrCreateInstance(document.querySelector('#tab-followup')).show();
        });
    });
</script>
@endsection