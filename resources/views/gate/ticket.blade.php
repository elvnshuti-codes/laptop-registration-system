@extends('layouts.app')

@section('title', 'Visitor Ticket')

@section('content')
<style>
    #visit-barcode { max-width: 100%; height: auto; }
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

                {{-- Barcode carries the code with hyphens; the printed text keeps the slashes. --}}
                <svg id="visit-barcode" data-code="{{ str_replace('/', '-', $visit->visit_code) }}" class="mb-2"></svg>
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
                    @if ($visit->arrival)
                        <tr>
                            <th class="text-muted fw-normal">Arrived by</th>
                            <td class="fw-semibold">{{ $visit->arrival->label() }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th class="text-muted fw-normal">Checked In</th>
                        <td class="fw-semibold">
                            {{ \Carbon\Carbon::parse($visit->gate_checked_in_at)->format('d M Y, H:i') }}
                        </td>
                    </tr>
                </table>

                @if ($visit->detail)
                    <div class="border rounded p-3 mt-3 text-start">
                        <div class="small text-uppercase text-muted mb-1">Visit details</div>
                        @if ($visit->detail->department || $visit->detail->person_to_see)
                            <div class="fw-semibold">
                                {{ collect([$visit->detail->department, $visit->detail->person_to_see])->filter()->implode(' · ') }}
                            </div>
                        @endif
                        <div>{{ $visit->detail->details }}</div>
                    </div>
                @endif

                @if ($visit->security)
                    <div class="border border-dark rounded p-3 mt-3 text-start">
                        <div class="small text-uppercase text-muted mb-1">Security officer</div>
                        <div class="fw-semibold">{{ $visit->security->force }} &middot; {{ $visit->security->officer_rank }}</div>
                        <div>Protecting: {{ $visit->security->vip_name }} <span class="text-muted">({{ $visit->security->vip_title }})</span></div>

                        @if ($visit->arms->isNotEmpty())
                            <div class="small text-uppercase text-muted mt-2 mb-1">Arms left at the gate</div>
                            @foreach ($visit->arms as $arm)
                                <div class="small">{{ $loop->iteration }}. {{ $arm->summary() }}</div>
                            @endforeach
                        @endif

                        <div class="small mt-2">
                            Locker key card: <strong>{{ $visit->visitor_card_number }}</strong>
                            @if ($visit->security->locker_number) &middot; Locker <strong>{{ $visit->security->locker_number }}</strong> @endif
                        </div>
                        <div class="small mt-1">Handled by <strong>{{ $visit->security->handler->name }}</strong></div>
                    </div>
                @endif

                @if ($visit->leftItems->isNotEmpty())
                    <div class="border rounded p-3 mt-3 text-start">
                        <div class="small text-uppercase text-muted mb-1">Left at the gate</div>
                        @foreach ($visit->leftItems as $item)
                            <div>
                                {{ $item->quantity }} &times; {{ $item->colour }} {{ $item->item_name }}
                                @if ($item->notes) <span class="text-muted">({{ $item->notes }})</span> @endif
                            </div>
                        @endforeach
                        <div class="small mt-2">Handled by <strong>{{ $visit->leftItems->first()->handler->name }}</strong></div>
                    </div>
                @endif
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

@section('scripts')
<script src="{{ asset('js/JsBarcode.all.min.js') }}"></script>
<script>
    var el = document.getElementById('visit-barcode');
    JsBarcode(el, el.dataset.code, {
        format: 'CODE128',
        width: 1.5,
        height: 50,
        displayValue: false,
        margin: 0
    });
</script>
@endsection