@extends('layouts.app')

@section('title', 'Checked Out')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

        <div class="card shadow border-0 text-center">
            <div class="card-header bg-success text-white py-3">
                <h4 class="mb-0">Visitor Checked Out</h4>
            </div>

            <div class="card-body p-4">
                <h5 class="mb-0">{{ $visit->name }}</h5>
                <div class="font-monospace text-muted mb-3">{{ $visit->visit_code }}</div>

                <table class="table table-borderless text-start mb-0">
                    <tr>
                        <th class="text-muted fw-normal">Checked In</th>
                        <td class="fw-semibold">{{ \Carbon\Carbon::parse($visit->gate_checked_in_at)->format('d M Y, H:i') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Checked Out</th>
                        <td class="fw-semibold">{{ \Carbon\Carbon::parse($visit->gate_checked_out_at)->format('d M Y, H:i') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Time on site</th>
                        <td class="fw-semibold">{{ $duration }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Visitor card</th>
                        <td class="fw-semibold">{{ $visit->visitor_card_number }} (collected)</td>
                    </tr>
                </table>
            </div>

            <div class="card-footer bg-white py-3">
                <a href="{{ route('gate.checkout.index') }}" class="btn btn-primary">Next visitor</a>
            </div>
        </div>

    </div>
</div>
@endsection