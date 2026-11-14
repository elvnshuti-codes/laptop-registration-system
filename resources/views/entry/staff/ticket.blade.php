@extends('layouts.app')

@section('title', 'Staff Ticket')

@section('content')
<style>
    @media print {
        nav, .navbar, .no-print, .alert { display: none !important; }
        body { background: #fff !important; }
        .ticket { box-shadow: none !important; border: 0 !important; }
    }
</style>

<div class="row justify-content-center">
    <div class="col-auto">
        <div class="card ticket shadow-sm border-0" style="width: 340px">
            <div class="card-body p-4 text-center">
                <div class="fw-bold fs-5">UNBS</div>
                <div class="text-uppercase small text-muted mb-3">Staff device pass</div>

                <div class="fw-semibold fs-5">{{ $entry->employee->name }}</div>
                <div class="small text-muted">
                    <span class="font-monospace">{{ $entry->employee->employee_id_number }}</span> &middot; {{ $entry->employee->department }}
                </div>

                <hr>

                <div class="text-start">
                    @foreach ($entry->devices as $row)
                        <div class="mb-2">
                            <div class="fw-semibold">{{ $row->device->owner_type === 'organization' ? 'UNBS' : 'Personal' }} {{ $row->device->device_type }} {{ $row->device->make_model }}</div>
                            <div class="small text-muted">
                                @if ($row->device->asset_tag) Tag {{ $row->device->asset_tag }} &middot; @endif
                                @if ($row->device->serial_number) Serial {{ $row->device->serial_number }} &middot; @endif
                                <span class="font-monospace">{{ $row->device->sticker_code }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <hr>

                <svg id="token-barcode" class="w-100"></svg>

                <div class="small text-muted mt-2">
                    {{ ucfirst($entry->location) }} &middot; In {{ $entry->checked_in_at->format('d M Y H:i') }}<br>
                    Officer: {{ $entry->checkedInBy->name }}
                </div>
                <div class="small fw-semibold mt-2">Keep this ticket. Show it when you leave.</div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-center mt-3 no-print">
            <button type="button" class="btn btn-primary" onclick="window.print()">Print ticket</button>
            <a href="{{ route('entry.staff.index') }}" class="btn btn-outline-secondary">Next check-in</a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/JsBarcode.all.min.js') }}"></script>
<script>
    // The token is only a barcode: the code is never printed as text.
    JsBarcode('#token-barcode', @json($entry->token), { format: 'CODE128', width: 2, height: 60, displayValue: false, margin: 0 });
</script>
@endsection