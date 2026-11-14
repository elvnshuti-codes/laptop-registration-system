@extends('layouts.app')

@section('title', 'Import Preview')

@section('content')
@php
    $c = $plan['counts'];

    // outcome => [label, badge colour]
    $outcomes = [
        'matched' => ['Matched', 'success'],
        'similar' => ['Similar person', 'warning text-dark'],
        'differ' => ['Different departments', 'danger'],
        'confused' => ['Confused', 'danger'],
        'nomatch' => ['No employee found', 'danger'],
        'skipped' => ['Skipped', 'secondary'],
    ];

    $attention = collect($plan['report'])->where('outcome', '!=', 'matched')->values();
    $matched = collect($plan['report'])->where('outcome', 'matched')->values();
    $nothingToDo = $c['create'] + $c['update'] + $c['devices'] === 0;

    $cards = [
        ['New employees', $c['create'], 'primary'],
        ['Employees updated', $c['update'], 'secondary'],
        ['UNBS laptops linked', $c['devices'], 'success'],
        ['Need your review', $c['attention'], 'warning'],
        ['Rows skipped', $c['skipped'], 'dark'],
    ];
@endphp

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h3 class="mb-0">Import preview</h3>
        <div class="text-muted">This is what would happen. Nothing has been saved yet.</div>
    </div>
    <a href="{{ route('staff.import.create') }}" class="btn btn-outline-secondary">&larr; Choose other files</a>
</div>

<div class="row g-3 mb-3">
    @foreach ($cards as [$label, $value, $colour])
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="display-6 fw-semibold text-{{ $colour }}">{{ $value }}</div>
                    <div class="text-muted small">{{ $label }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if (count($plan['unknown_departments']) > 0)
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-info-subtle py-3">
            <h5 class="mb-0">Department names I did not recognise</h5>
            <small>Choose the official department each one means. I remember your answers for the next import. Leave a row as "Not sure yet" to decide later.</small>
        </div>
        <form method="POST" action="{{ route('staff.import.departments', $token) }}">
            @csrf  
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr><th>Written as</th><th>Rows</th><th>Found in</th><th style="min-width: 280px">Official department</th><th style="min-width: 260px">Or type a new department</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($plan['unknown_departments'] as $i => $u)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $u['text'] }}
                                    <input type="hidden" name="map[{{ $i }}][text]" value="{{ $u['text'] }}">
                                </td>
                                <td>{{ $u['count'] }}</td>
                                <td class="small text-muted">{{ $u['lists'] }}</td>
                                <td>
                                    <select name="map[{{ $i }}][department]" class="form-select form-select-sm">
                                        <option value="">Not sure yet</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                        <option value="none">No department (ignore this text)</option>
                                    </select>
                                </td>
                                </td>
                                <td>
                                    <input type="text" name="map[{{ $i }}][new]" class="form-control form-control-sm"
                                           placeholder="e.g. Procurement and Disposal" maxlength="120" autocomplete="off">
                                </td>
                            </tr>   
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                <button type="submit" class="btn btn-info">Save and refresh preview</button>
            </div>
        </form>
    </div>
@endif

@if ($nothingToDo)
    <div class="alert alert-info">There is nothing to import from these files.</div>
@else
    <form method="POST" action="{{ route('staff.import.apply', $token) }}" class="mb-4"
          onsubmit="return confirm('Apply this import now?')">
        @csrf
        <button type="submit" class="btn btn-success btn-lg">Apply import</button>
        <a href="{{ route('staff.import.create') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
    </form>
@endif

@if ($attention->isNotEmpty() || count($plan['skipped']) > 0)
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-warning py-3"><h5 class="mb-0">Needs your attention</h5></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Row</th><th>Asset tag</th><th>Laptop list says</th><th>Result</th><th>What happens</th></tr>
                </thead>
                <tbody>
                    @foreach ($attention as $r)
                        <tr>
                            <td>{{ $r['row'] }}</td>
                            <td class="font-monospace">{{ $r['tag'] }}</td>
                            <td>
                                <div class="fw-semibold">{{ $r['laptop_name'] }}</div>
                                <div class="small text-muted">{{ $r['laptop_dept'] ?: 'no department' }}</div>
                            </td>
                            <td><span class="badge bg-{{ $outcomes[$r['outcome']][1] }}">{{ $outcomes[$r['outcome']][0] }}</span></td>
                            <td class="small">
                                {{ $r['note'] }}
                                @if ($r['employee_id'])
                                    <div class="text-muted">Linked to <span class="font-monospace">{{ $r['employee_id'] }}</span></div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @foreach ($plan['skipped'] as $s)
                        <tr>
                            <td>{{ $s['row'] }}</td>
                            <td class="text-muted">&mdash;</td>
                            <td>
                                <div class="fw-semibold">{{ $s['what'] }}</div>
                                <div class="small text-muted">{{ $s['list'] }}</div>
                            </td>
                            <td><span class="badge bg-secondary">Skipped</span></td>
                            <td class="small">{{ $s['reason'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($matched->isNotEmpty())
    <details class="card shadow-sm border-0 mb-3">
        <summary class="card-header bg-light py-3 fw-semibold">Matched laptops ({{ $matched->count() }})</summary>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Row</th><th>Asset tag</th><th>Laptop list says</th><th>Linked to</th></tr></thead>
                <tbody>
                    @foreach ($matched as $r)
                        <tr>
                            <td>{{ $r['row'] }}</td>
                            <td class="font-monospace">{{ $r['tag'] }}</td>
                            <td>{{ $r['laptop_name'] }} <span class="text-muted small">&middot; {{ $r['laptop_dept'] }}</span></td>
                            <td>{{ $r['employee_name'] }} <span class="font-monospace small text-muted">{{ $r['employee_id'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endif

@if (count($plan['people']) > 0)
    <details class="card shadow-sm border-0 mb-3">
        <summary class="card-header bg-light py-3 fw-semibold">Employees from the list ({{ count($plan['people']) }})</summary>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Row</th><th>Employee ID</th><th>Name</th><th>Position</th><th>Department</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach ($plan['people'] as $p)
                        <tr>
                            <td>{{ $p['row'] }}</td>
                            <td class="font-monospace">{{ $p['id'] }}</td>
                            <td>{{ $p['name'] }}</td>
                            <td>{{ $p['position'] ?? '—' }}</td>
                            <td>{{ $p['department'] ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $p['action'] === 'create' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $p['action'] === 'create' ? 'Add' : 'Update' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endif
@endsection