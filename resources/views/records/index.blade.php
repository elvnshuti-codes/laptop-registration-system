@extends('layouts.app')

@section('title', 'Visitor Records')

@section('content')
@php
    $badge = fn ($s) => match ($s) {
        'Served' => 'bg-success',
        'Left unserved' => 'bg-danger',
        'Waiting' => 'bg-warning text-dark',
        default => 'bg-secondary',
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Visitor records</h3>
        <div class="text-muted">Find any visitor, whenever they visited.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('records.index', ['status' => 'followup']) }}" class="btn btn-outline-danger btn-sm">Follow-up list</a>
@can('export reports')
        <a href="{{ route('records.csv', $csvQuery) }}" class="btn btn-outline-primary btn-sm">Download results (CSV)</a>
@endcan
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('records.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label for="q" class="form-label small fw-semibold mb-1">Name, visit code, ID number or phone</label>
                <input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control form-control-sm" autofocus>
            </div>
            <div class="col-6 col-md-2">
                <label for="status" class="form-label small fw-semibold mb-1">Status</label>
                <select id="status" name="status" class="form-select form-select-sm">
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(request('status', 'all') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="from" class="form-label small fw-semibold mb-1">From</label>
                <input type="date" id="from" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label for="to" class="form-label small fw-semibold mb-1">To</label>
                <input type="date" id="to" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label for="purpose" class="form-label small fw-semibold mb-1">Purpose</label>
                <select id="purpose" name="purpose" class="form-select form-select-sm">
                    <option value="">All purposes</option>
                    @foreach ($purposes as $purpose)
                        <option value="{{ $purpose }}" @selected(request('purpose') === $purpose)>{{ $purpose }}</option>
                    @endforeach
                </select>
            </div>
            @if (count($allowedAreas) > 1)
                <div class="col-6 col-md-2">
                    <label for="area" class="form-label small fw-semibold mb-1">Area</label>
                    <select id="area" name="area" class="form-select form-select-sm">
                        <option value="all" @selected(request('area', 'all') === 'all')>Both</option>
                        <option value="standard" @selected(request('area') === 'standard')>Standard House</option>
                        <option value="sample" @selected(request('area') === 'sample')>Sample House</option>
                    </select>
                </div>
            @endif
            <div class="col-6 col-md-1 d-grid">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
            </div>
            <div class="col-6 col-md-1 d-grid">
                <a href="{{ route('records.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<p class="text-muted small">
    {{ $total }} visitor(s) found
    @if ($total > $rows->count())
        &middot; showing the latest {{ $rows->count() }}. Narrow the search (dates or text) to see older ones.
    @endif
</p>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Visitor</th>
                        <th>Mobile</th>
                        <th>Purpose</th>
                        <th>Checked in</th>
                        <th>Time on premises</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        @php $v = $r['visit']; $u = $r['unserved']; @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $v->name }}</div>
                                <div class="text-muted small font-monospace">{{ $v->visit_code }}</div>
                                @if (count($allowedAreas) > 1)
                                    <div class="text-muted small">{{ $r['area'] }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($v->visitor_mobile_number)
                                    <a href="tel:{{ $v->visitor_mobile_number }}">{{ $v->visitor_mobile_number }}</a>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td>{{ $r['purpose'] }}</td>
                            <td>{{ $r['checkIn']->format('d M Y, H:i') }}</td>
                            <td>{{ $r['duration'] }}</td>
                            <td>
                                <span class="badge {{ $badge($r['status']) }}">{{ $r['status'] }}</span>
                                @if ($u)
                                    <div class="small mt-1">{{ $u->reason }}</div>
                                    @if ($u->acknowledged_at)
                                        <div class="small text-success">Acknowledged</div>
                                    @else
                                        <div class="small text-danger">Not acknowledged</div>
                                    @endif
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('records.show', $v) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No visitors match this search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
