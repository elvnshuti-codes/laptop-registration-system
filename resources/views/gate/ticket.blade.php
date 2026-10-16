@extends('layouts.app')

@section('title', 'Visitor Ticket')

@section('content')
<style>
    @media print {
        .navbar, .no-print, .alert { display: none !important; }
        body { background: #fff !important; }
        .ticket-card { box-shadow: none !important; border: 2px dashed #000 !important; }
    }
</style>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">

        <div class="card ticket-card shadow border-0 text-center">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0">UNBS &middot; Visitor Pass</h5>
            </div>

            <div class="card-body p-4">
                <div class="text-muted small text-uppercase">Visit Code</div>
                <div class="display-6 fw-bold font-monospace my-2">{{ $visit->visit_code }}</div>
                <hr>

                <table class="table table-borderless text-start mb-0">
                    <tr>
                        <th class="text-muted fw-normal">Name</th>
                        <td class="fw-semibold">{{ $visit->name }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Purpose</th>
                        <td class="fw-semibold">
                            {{ $visit->purpose }}@if ($visit->purpose_other): {{ $visit->purpose_other }}@endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Visitor Card</th>
                        <td class="fw-semibold">{{ $visit->visitor_card_number }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Checked In</th>
                        <td class="fw-semibold">
                            {{ \Carbon\Carbon::parse($visit->gate_checked_in_at)->format('d M Y, H:i') }}
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card-footer bg-white py-3 small text-muted">
                Please keep this pass visible and return it at the gate when leaving.
            </div>
        </div>

        <div class="d-grid gap-2 mt-4 no-print">
            <button type="button" class="btn btn-primary btn-lg" onclick="window.print()">Print Ticket</button>
            <a href="{{ route('gate.create') }}" class="btn btn-outline-secondary">Check in another visitor</a>
        </div>

    </div>
</div>
@endsection