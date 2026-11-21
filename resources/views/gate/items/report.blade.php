@extends('layouts.app')

@section('title', 'Gate Items Report')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-11">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0">Gate items and arms: accountability</h4>
                <small class="opacity-75">Who left belongings or weapons, who collected them, and who verified the records.</small>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">From</label>
                        <input type="date" name="from" value="{{ $from }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">To</label>
                        <input type="date" name="to" value="{{ $to }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Show</label>
                        <select name="status" class="form-select">
                            <option value="all" @selected($status === 'all')>All records</option>
                            <option value="unapproved" @selected($status === 'unapproved')>Never approved</option>
                            <option value="late" @selected($status === 'late')>Approved after the visitor left</option>
                            <option value="notreturned" @selected($status === 'notreturned')>Not returned</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit">Apply</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            @foreach ([
                ['Records', $summary['all'], 'secondary'],
                ['Officers who left arms', $summary['armed'], 'dark'],
                ['Waivers', $summary['waived'], 'dark'],
                ['Collected their arms', $summary['armed_collected'], 'success'],
                ['Arms still held', $summary['armed_held'], 'warning'],
                ['Not returned', $summary['not_returned'], 'danger'],
                ['Never approved', $summary['unapproved'], 'danger'],
                ['Approved late', $summary['late'], 'warning'],
            ] as [$label, $value, $colour])
                <div class="col-6 col-md-3 col-xl">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body py-3">
                            <div class="fs-3 fw-bold text-{{ $colour === 'warning' ? 'warning-emphasis' : $colour }}">{{ $value }}</div>
                            <div class="small text-muted">{{ $label }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Visitor</th>
                            <th>Left at the gate</th>
                            <th>Taken in by</th>
                            <th>Collected</th>
                            <th>Verified</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visits as $visit)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $visit->name }}</div>
                                    @if ($visit->security)
                                        <div class="small">{{ $visit->security->force }} &middot; {{ $visit->security->officer_rank }}</div>
                                    @endif
                                    <div class="small text-muted font-monospace">{{ $visit->visit_code }}</div>
                                    <div class="small text-muted">{{ \Carbon\Carbon::parse($visit->gate_checked_in_at)->format('d M Y, H:i') }}</div>
                                </td>
                                <td class="small">
                                    @foreach ($visit->arms as $arm) <div>{{ $arm->summary() }}</div> @endforeach
                                    @foreach ($visit->leftItems as $item)
                                        <div>{{ $item->quantity }} &times; {{ $item->colour }} {{ $item->item_name }}</div>
                                    @endforeach
                                    @foreach ($visit->waivers as $waiver)
                                        <div class="text-danger">Waived: {{ $waiver->label() }}</div>
                                    @endforeach
                                    @if ($visit->waivers->isNotEmpty())
                                        <div class="text-muted">{{ $visit->waivers->first()->reason }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ ($visit->security?->handler ?? $visit->leftItems->first()?->handler ?? $visit->waivers->first()?->recorder)?->name }}
                                </td>
                                <td class="small">
                                    @if ($visit->rows->isEmpty())
                                        <span class="text-muted">Nothing left</span>
                                    @elseif ($visit->stillHeld)
                                        <span class="badge bg-warning text-dark">Still held</span>
                                    @elseif ($visit->allReturned)
                                        <span class="badge bg-success">Collected</span>
                                        <div class="text-muted">{{ optional($visit->rows->max('returned_at'))->format('d M, H:i') }}</div>
                                    @else
                                        <span class="badge bg-danger">Not all returned</span>
                                        @foreach ($visit->rows->whereNull('returned_at') as $row)
                                            <div class="text-danger">{{ $row->not_returned_reason }}</div>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="small">
                                    @if ($visit->allApproved)
                                        @php $first = $visit->approvalRows->first(); @endphp
                                        <span class="badge {{ $visit->anyLate ? 'bg-warning text-dark' : 'bg-success' }}">
                                            {{ $visit->anyLate ? 'Approved late' : 'Approved' }}
                                        </span>
                                        <div>{{ $first->approver?->name }}</div>
                                        <div class="text-muted">{{ $first->approved_at->format('d M Y, H:i') }}</div>
                                    @else
                                        <span class="badge bg-danger">Never approved</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted p-4">No records for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection