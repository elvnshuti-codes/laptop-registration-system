@extends('layouts.app')

@section('title', 'Reports')

@section('content')
@php
    $areaLabels = ['standard' => 'Standard House', 'sample' => 'Sample House'];
    $bar = fn ($n, $max) => $max > 0 ? round($n / $max * 100) : 0;
    $maxHour = $hours->max() ?? 0;
    $maxDay = $weekdays->max('count') ?? 0;
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Reports</h3>
        <div class="text-muted">
            {{ $f['from']->format('d M Y') }} &ndash; {{ $f['to']->format('d M Y') }}
            &middot; {{ collect($f['areas'])->map(fn ($a) => $areaLabels[$a])->implode(' + ') }}
        </div>
    </div>
    <div class="d-flex gap-2">
@can('export reports')
        <a href="{{ route('reports.csv', $csvQuery + ['type' => 'visits']) }}" class="btn btn-outline-primary btn-sm">Download visitors (CSV)</a>
        <a href="{{ route('reports.csv', $csvQuery + ['type' => 'summary']) }}" class="btn btn-outline-primary btn-sm">Download summary (CSV)</a>
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

{{-- Filters --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label for="preset" class="form-label small fw-semibold mb-1">Period</label>
                <select id="preset" name="preset" class="form-select form-select-sm">
                    @foreach ($presets as $key => $label)
                        <option value="{{ $key }}" @selected($f['preset'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="from" class="form-label small fw-semibold mb-1">From</label>
                <input type="date" id="from" name="from" value="{{ $f['from']->format('Y-m-d') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label for="to" class="form-label small fw-semibold mb-1">To</label>
                <input type="date" id="to" name="to" value="{{ $f['to']->format('Y-m-d') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-1">
                <label for="group" class="form-label small fw-semibold mb-1">Group by</label>
                <select id="group" name="group" class="form-select form-select-sm">
                    @foreach ($groups as $key => $label)
                        <option value="{{ $key }}" @selected($f['group'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if (count($allowedAreas) > 1)
                <div class="col-6 col-md-2">
                    <label for="area" class="form-label small fw-semibold mb-1">Area</label>
                    <select id="area" name="area" class="form-select form-select-sm">
                        <option value="all" @selected($f['area'] === 'all')>Both</option>
                        <option value="standard" @selected($f['area'] === 'standard')>Standard House</option>
                        <option value="sample" @selected($f['area'] === 'sample')>Sample House</option>
                    </select>
                </div>
            @endif
            <div class="col-6 col-md-2">
                <label for="purpose" class="form-label small fw-semibold mb-1">Purpose</label>
                <select id="purpose" name="purpose" class="form-select form-select-sm">
                    <option value="">All purposes</option>
                    @foreach ($purposes as $purpose)
                        <option value="{{ $purpose }}" @selected($f['purpose'] === $purpose)>{{ $purpose }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="reason" class="form-label small fw-semibold mb-1">Unserved reason</label>
                <select id="reason" name="reason" class="form-select form-select-sm">
                    <option value="">All reasons</option>
                    @foreach ($reasonOptions as $reason)
                        <option value="{{ $reason }}" @selected($f['reason'] === $reason)>{{ $reason }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-1 d-grid">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </div>
        </form>
    </div>
</div>

{{-- Headline numbers --}}
<div class="row g-3 mb-4 text-center">
    <div class="col-6 col-md-2">
        <div class="border rounded bg-white p-3">
            <div class="fs-2 fw-bold">{{ $summary['visitors'] }}</div>
            <div class="text-muted small">Visitors</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="border rounded bg-white p-3">
            <div class="fs-2 fw-bold text-success">{{ $summary['served'] }}</div>
            <div class="text-muted small">Served</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="border rounded bg-white p-3">
            <div class="fs-2 fw-bold text-danger">{{ $summary['left_unserved'] }}</div>
            <div class="text-muted small">Left unserved</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="border rounded bg-white p-3">
            <div class="fs-2 fw-bold text-warning">{{ $summary['waiting'] }}</div>
            <div class="text-muted small">Still waiting</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="border rounded bg-white p-3">
            <div class="fs-2 fw-bold">{{ $summary['rate'] !== null ? $summary['rate'] . '%' : '—' }}</div>
            <div class="text-muted small">Service rate</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="border rounded bg-white p-3">
            <div class="fs-2 fw-bold">{{ $summary['returning'] }}</div>
            <div class="text-muted small">Returning of {{ $summary['distinct'] }} people</div>
        </div>
    </div>
</div>
<p class="text-muted small mb-4">
    Visitors are counted on the day they checked in at the Gate. Service rate = served &divide; (visitors &minus; still waiting).
    A returning visitor is a person (by ID number) who visited before this period or more than once in it.
    @if ($summary['other'] > 0)
        <br>{{ $summary['other'] }} visitor(s) left without being served and without a recorded reason (usually older test records).
    @endif
</p>

{{-- Visitors over time --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white py-3">
        <h5 class="mb-0">Visitors by {{ strtolower($groups[$f['group']]) }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ $groups[$f['group']] }}</th>
                        <th class="text-end">Visitors</th>
                        <th class="text-end">Served</th>
                        <th class="text-end">Left unserved</th>
                        <th class="text-end">Still waiting</th>
                        <th class="text-end">Service rate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($periodRows as $p)
                        <tr>
                            <td>{{ $p['label'] }}</td>
                            <td class="text-end">{{ $p['visitors'] }}</td>
                            <td class="text-end">{{ $p['served'] }}</td>
                            <td class="text-end">{{ $p['left_unserved'] }}</td>
                            <td class="text-end">{{ $p['waiting'] }}</td>
                            <td class="text-end">{{ $p['rate'] !== null ? $p['rate'] . '%' : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nothing to show for this period yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- By purpose --}}
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white py-3"><h5 class="mb-0">By purpose of visit</h5></div>
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Purpose</th>
                            <th class="text-end">Visitors</th>
                            <th class="text-end">Served</th>
                            <th class="text-end">Left unserved</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purposeRows as $p)
                            <tr>
                                <td>{{ $p['label'] }}</td>
                            <td class="text-end">{{ $p['visitors'] }}</td>
                                <td class="text-end">{{ $p['served'] }}</td>
                                <td class="text-end">{{ $p['left_unserved'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No visitors in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Busiest weekdays --}}
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white py-3"><h5 class="mb-0">Busiest weekdays</h5></div>
            <div class="card-body">
                @foreach ($weekdays as $d)
                    <div class="d-flex align-items-center mb-2">
                        <div style="width: 6.5rem;">{{ $d['name'] }}</div>
                        <div class="progress flex-grow-1" style="height: .9rem;">
                            <div class="progress-bar" style="width: {{ $bar($d['count'], $maxDay) }}%"></div>
                        </div>
                        <div class="text-end" style="width: 3rem;">{{ $d['count'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Busiest hours --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white py-3"><h5 class="mb-0">Busiest hours (check-ins at the Gate)</h5></div>
    <div class="card-body">
        @forelse ($hours as $hour => $count)
            <div class="d-flex align-items-center mb-2">
                <div style="width: 4rem;">{{ $hour }}</div>
                <div class="progress flex-grow-1" style="height: .9rem;">
                    <div class="progress-bar" style="width: {{ $bar($count, $maxHour) }}%"></div>
                </div>
                <div class="text-end" style="width: 3rem;">{{ $count }}</div>
            </div>
        @empty
            <p class="text-muted mb-0">No visitors in this period.</p>
        @endforelse
    </div>
</div>

{{-- Left unserved --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-danger text-white py-3">
        <h5 class="mb-0">Visitors who left without being served</h5>
        @if ($f['reason'])
            <small class="opacity-75">Showing only: {{ $f['reason'] }}</small>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4 text-center">
            <div class="col-6 col-md-3">
                <div class="border rounded p-3">
                    <div class="fs-3 fw-bold">{{ $unserved['total'] }}</div>
                    <div class="text-muted small">Left unserved</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-3">
                    <div class="fs-3 fw-bold {{ $unserved['pending'] > 0 ? 'text-danger' : '' }}">{{ $unserved['pending'] }}</div>
                    <div class="text-muted small">Not yet acknowledged</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-3">
                    <div class="fs-3 fw-bold">{{ $unserved['avgAckMinutes'] !== null ? $unserved['avgAckMinutes'] . ' min' : '—' }}</div>
                    <div class="text-muted small">Average time to acknowledge</div>
                </div>
            </div>
            <div class="co6 col-md-3">
                <div class="border rounded p-3">
                    <div class="fs-3 fw-bold">{{ $unserved['sentBackVisitors'] }}</div>
                    <div class="text-muted small">Sent back to Reception</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <h6 class="fw-semibold">By reason</h6>
                <table class="table table-sm align-middle">
                    <thead>
                        <tr><th>Reason</th><th class="text-end">Visitors</th><th class="text-end">Share</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($unserved['byReason'] as $reason => $count)
                            <tr>
                                <td>{{ $reason }}</td>
                                <td class="text-end">{{ $count }}</td>
                                <td class="text-end">{{ $unserved['total'] > 0 ? round($count / $unserved['total'] * 100) : 0 }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">No visitors left unserved in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="col-lg-5">
                <h6 class="fw-semibold">By area</h6>
                <table class="table table-sm align-middle">
                    <tbody>
                        @forelse ($unserved['byArea'] as $area => $count)
                            <tr><td>{{ $area }}</td><td class="text-end">{{ $count }}</td></tr>
                        @empty
                            <tr><td class="text-muted">None.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <h6 class="fw-semibold mt-3">Process checks</h6>
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        <tr><td>Send-backs by Entry (times)</td><td class="text-end">{{ $unserved['sentBackEvents'] }}</td></tr>
                        <tr><td>Visitors who skipped Entry at check-in</td><td class="text-end">{{ $unserved['skipped'] }}</td></tr>
                        <tr><td>Acknowledged by Reception</td><td class="text-end">{{ $unserved['acknowledged'] }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Workload (administrator only) --}}
@if ($handled->isNotEmpty())
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0">Visitors handled</h5>
            <small class="opacity-75">People with the workload permission see every receptionist; everyone else sees only their own figure. Standard House: who received the visitor. Sample Reception: who marked them served.</small>
        </div>
        <div class="card-body p-0">
            <table class="table align-middle mb-0">
                <thead><tr><th>Receptionist</th><th class="text-end">Visitors served</th></tr></thead>
                <tbody>
                    @forelse ($handled as $h)
                        <tr><td>{{ $h['name'] }}</td><td class="text-end">{{ $h['count'] }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-4">No served visitors in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

@section('scripts')
<script>
    // Typing your own dates switches the period to "Custom dates".
    $(function () {
        $('#from, #to').on('change', function () {
            $('#preset').val('custom');
        });
    });
</script>
@endsection
