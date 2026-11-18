@extends('layouts.app')

@section('title', 'Staff Check-Out Summary')

@section('content')
<style>
    @media print {
        .navbar, .no-print, .alert { display: none !important; }
        body { background: #fff !important; }
        .slip-card { box-shadow: none !important; border: 2px dashed #000 !important; }
    }
</style>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

        <div class="card slip-card shadow border-0">
            <div class="card-header bg-dark text-white py-3 text-center">
                <h5 class="mb-0">UNBS &middot; Staff Device Check-Out</h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless table-sm mb-3">
                    <tr>
                        <th class="text-muted fw-normal">Employee</th>
                        <td class="fw-semibold">{{ $employee->name }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Staff ID</th>
                        <td class="font-monospace">{{ $employee->employee_id_number }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Department</th>
                        <td>{{ $employee->department }}</td>
                    </tr>
                </table>

                @if ($entryRows->isNotEmpty())
                    <div class="small text-uppercase text-muted mb-2">Devices checked out</div>
                    @foreach ($entryRows as $row)
                        <div class="border rounded p-3 mb-2">
                            <div class="fw-semibold">{{ $row->device->label() }}</div>
                            @if ($row->device->asset_tag)
                                <div class="small text-muted">Asset tag {{ $row->device->asset_tag }}</div>
                            @endif
                            <div class="small mt-1">Checked in: {{ $row->entry->checked_in_at->format('d M Y, H:i') }}</div>
                            <div class="small">Checked out: <strong>{{ $row->checked_out_at->format('d M Y, H:i') }}</strong></div>
                        </div>
                    @endforeach
                @endif

                @if ($issuedRows->isNotEmpty())
                    <div class="small text-uppercase text-muted mb-2 mt-3">Devices that left without a check-in</div>
                    @foreach ($issuedRows as $row)
                        <div class="border rounded p-3 mb-2">
                            <div class="fw-semibold">{{ $row->device->label() }}</div>
                            @if ($row->device->asset_tag)
                                <div class="small text-muted">Asset tag {{ $row->device->asset_tag }}</div>
                            @endif
                            <div class="small mt-1">Issued: <strong>{{ $row->issued_at->format('d M Y, H:i') }}</strong></div>
                            @if ($row->collected_at)
                                <div class="small">Collected: {{ $row->collected_at->format('d M Y, H:i') }}</div>
                            @endif
                            <div class="small">Checked out: {{ $row->checked_out_at->format('d M Y, H:i') }}</div>
                        </div>
                    @endforeach
                @endif

                <div class="row mt-4 pt-3 border-top small text-muted">
                    <div class="col-6">Employee signature:<br><br>______________________</div>
                    <div class="col-6">Entry officer: <strong class="text-dark">{{ auth()->user()->name }}</strong></div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mt-4 no-print">
            <button type="button" class="btn btn-primary btn-lg" onclick="window.print()">Print</button>
            <a href="{{ route('entry.staff-out.start') }}" class="btn btn-outline-secondary">Continue</a>
        </div>

    </div>
</div>
@endsection