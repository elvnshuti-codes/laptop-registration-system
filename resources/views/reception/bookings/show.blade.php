@extends('layouts.app')

@section('title', 'Booking ' . $booking->booking_code)

@section('content')
<style>
    #booking-barcode { max-width: 100%; height: auto; }
    @media print {
        .navbar, .no-print, .alert { display: none !important; }
        body { background: #fff !important; }
    }
</style>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

        <div class="card shadow border-0 text-center">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0">UNBS &middot; Visit Booking</h5>
            </div>

            <div class="card-body p-4">
                <div class="small text-muted text-uppercase">Booking code</div>
                <div class="display-6 fw-bold font-monospace mb-2">{{ $booking->booking_code }}</div>
                <svg id="booking-barcode" data-code="{{ $booking->booking_code }}" class="mb-3"></svg>

                <table class="table table-borderless text-start mb-0">
                    <tr>
                        <th class="text-muted fw-normal">Visitor</th>
                        <td class="fw-semibold">{{ $booking->name }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Meeting with</th>
                        <td class="fw-semibold">{{ $booking->meeting_with }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Purpose</th>
                        <td class="fw-semibold">{{ $booking->purpose }}@if ($booking->purpose_other): {{ $booking->purpose_other }}@endif</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Expected</th>
                        <td class="fw-semibold">{{ $booking->expected_date->format('D, d M Y') }}@if ($booking->expected_time), {{ substr($booking->expected_time, 0, 5) }}@endif</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Bring</th>
                        <td class="fw-semibold">
                            {{ $booking->id_document_type === 'Other' && $booking->id_document_other ? $booking->id_document_other : $booking->id_document_type }}
                            <span class="font-monospace">&middot; {{ $booking->id_document_number }}</span>
                        </td>
                    </tr>
                    @if ($booking->device_note)
                        <tr>
                            <th class="text-muted fw-normal">Devices</th>
                            <td>{{ $booking->device_note }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th class="text-muted fw-normal">Status</th>
                        <td>
                            @if ($booking->status === 'arrived')
                                <span class="badge bg-success">Arrived</span>
                            @elseif ($booking->status === 'cancelled')
                                <span class="badge bg-secondary">Cancelled</span>
                            @else
                                <span class="badge bg-warning text-dark">Expected</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card-footer bg-white small text-muted">
                Show this code at the Gate on the expected date only, and carry the document above.
            </div>
        </div>

        <div class="d-grid gap-2 mt-3 no-print">
            <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
            <a href="{{ route('reception.bookings.create') }}" class="btn btn-outline-secondary">Book another visitor</a>
            <a href="{{ route('reception.bookings.index') }}" class="btn btn-outline-secondary">All bookings</a>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/JsBarcode.all.min.js') }}"></script>
<script>
    var el = document.getElementById('booking-barcode');
    JsBarcode(el, el.dataset.code, {
        format: 'CODE128',
        width: 2,
        height: 50,
        displayValue: false,
        margin: 0
    });
</script>
@endsection