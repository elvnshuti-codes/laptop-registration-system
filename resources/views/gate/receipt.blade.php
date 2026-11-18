@extends('layouts.app')

@section('title', 'Items Returned')

@section('content')
<style>
    @media print {
        .navbar, .no-print, .alert { display: none !important; }
        body { background: #fff !important; }
        .receipt-card { box-shadow: none !important; border: 2px dashed #000 !important; }
    }
</style>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

        <div class="card receipt-card shadow border-0">
            <div class="card-header bg-dark text-white py-3 text-center">
                <h5 class="mb-0">UNBS &middot; Items Returned</h5>
            </div>

            <div class="card-body p-4">
                <table class="table table-borderless table-sm mb-3">
                    <tr>
                        <th class="text-muted fw-normal">Visitor</th>
                        <td class="fw-semibold">{{ $visit->name }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Visit code</th>
                        <td class="font-monospace">{{ $visit->visit_code }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Visitor card</th>
                        <td>{{ $visit->visitor_card_number }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Checked out</th>
                        <td>{{ \Carbon\Carbon::parse($visit->gate_checked_out_at)->format('d M Y, H:i') }}</td>
                    </tr>
                </table>

                @if ($visit->security)
                    <div class="small text-uppercase text-muted mb-1">Security officer</div>
                    <div class="mb-3">
                        {{ $visit->security->force }} &middot; {{ $visit->security->officer_rank }}
                        &mdash; protecting {{ $visit->security->vip_name }} ({{ $visit->security->vip_title }})
                    </div>
                @endif

                @if ($visit->arms->isNotEmpty())
                    <div class="small text-uppercase text-muted mb-1">Arms</div>
                    <ul class="list-unstyled mb-3">
                        @foreach ($visit->arms as $arm)
                            <li class="mb-2">
                                <div>{{ $arm->summary() }}</div>
                                @if ($arm->returned_at)
                                    <div class="small text-success">
                                        &#10003; Returned {{ $arm->returned_at->format('d M Y, H:i') }}
                                        &middot; {{ $arm->returner?->name }}
                                    </div>
                                @else
                                    <div class="small text-danger">
                                        &#10007; Not returned: {{ $arm->not_returned_reason }}
                                        &middot; recorded by {{ $arm->returner?->name }}
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($visit->leftItems->isNotEmpty())
                    <div class="small text-uppercase text-muted mb-1">Belongings</div>
                    <ul class="list-unstyled mb-3">
                        @foreach ($visit->leftItems as $item)
                            <li class="mb-2">
                                <div>{{ $item->quantity }} &times; {{ $item->colour }} {{ $item->item_name }}</div>
                                @if ($item->returned_at)
                                    <div class="small text-success">
                                        &#10003; Returned {{ $item->returned_at->format('d M Y, H:i') }}
                                        &middot; {{ $item->returner?->name }}
                                    </div>
                                @else
                                    <div class="small text-danger">
                                        &#10007; Not returned: {{ $item->not_returned_reason }}
                                        &middot; recorded by {{ $item->returner?->name }}
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="row mt-4 pt-3 border-top small text-muted">
                    <div class="col-6">Visitor signature:<br><br>______________________</div>
                    <div class="col-6">Gate officer: <strong class="text-dark">{{ auth()->user()->name }}</strong></div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mt-4 no-print">
            <button type="button" class="btn btn-primary btn-lg" onclick="window.print()">Print Receipt</button>
            <a href="{{ route('gate.checkout.done', $visit) }}" class="btn btn-outline-secondary">Continue</a>
        </div>

    </div>
</div>
@endsection