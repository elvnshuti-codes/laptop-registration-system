@extends('layouts.app')

@section('title', 'Staff Stickers')

@section('content')
<style>
    .label-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6mm; margin-bottom: 8mm; }
    .label { border: 1px dashed #999; padding: 3mm; text-align: center; break-inside: avoid; background: #fff; }
    .label .kind { font-size: 10px; letter-spacing: 1px; font-weight: 700; color: #222; }
    .label .who { font-size: 15px; font-weight: 700; line-height: 1.15; margin-top: 1mm; }
    .label .meta { font-size: 10px; color: #333; }
    .label svg { max-width: 100%; height: auto; }
    .group-head { font-weight: 700; font-size: 13px; border-bottom: 2px solid #222; margin-bottom: 3mm; padding-bottom: 1mm; break-after: avoid; }
    .checklist { break-after: page; }
    @media print {
        .navbar, .no-print, .alert { display: none !important; }
        body { background: #fff !important; }
    }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 no-print">
    <div>
        <h4 class="mb-0">{{ $title }}</h4>
        <small class="text-muted">{{ $ids->count() }} sticker(s) &middot; {{ $employees->count() }} employee(s)</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select id="cols" class="form-select form-select-sm" style="width:auto" aria-label="Stickers per row">
            <option value="3">3 per row</option>
            <option value="2">2 per row (long codes)</option>
        </select>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Back</a>
        @if ($ids->isNotEmpty())
            <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
        @endif
    </div>
</div>

@if ($ids->isEmpty())
    <div class="alert alert-warning">There are no stickers to print.</div>
@else
    @if ($employees->count() > 1)
        <div class="checklist">
            <h5>{{ $title }} &middot; checklist ({{ now()->format('d M Y') }})</h5>
            <table class="table table-sm table-bordered align-middle">
                <thead>
                    <tr><th style="width:30px"></th><th>Employee</th><th>ID</th><th>Department</th><th>Device</th><th>Barcode</th><th>Serial</th></tr>
                </thead>
                <tbody>
                    @foreach ($employees as $employee)
                        @foreach ($employee->devices as $device)
                            <tr>
                                <td>&#9744;</td>
                                <td>{{ $employee->name }}</td>
                                <td class="font-monospace">{{ $employee->employee_id_number }}</td>
                                <td>{{ $employee->department }}</td>
                                <td>{{ $device->owner_type === 'organization' ? 'UNBS' : 'Personal' }} {{ $device->device_type }}</td>
                                <td class="font-monospace">{{ $device->sticker_code }}</td>
                                <td class="font-monospace">{{ $device->serial_number }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @foreach ($employees as $employee)
        <div class="group-head">{{ $employee->name }} &middot; {{ $employee->employee_id_number }} &middot; {{ $employee->department }}</div>
        <div class="label-grid">
            @foreach ($employee->devices as $device)
                <div class="label">
                    <div class="kind">{{ $device->owner_type === 'organization' ? 'UNBS' : 'PERSONAL' }} {{ strtoupper($device->device_type) }}</div>
                    <svg class="barcode" data-code="{{ $device->sticker_code }}"></svg>
                    <div class="who">{{ $employee->name }}</div>
                    <div class="meta">{{ $employee->employee_id_number }}@if ($device->serial_number) &middot; S/N {{ $device->serial_number }}@endif</div>
                </div>
            @endforeach
        </div>
    @endforeach

    <form method="POST" action="{{ route('staff.stickers.printed') }}" class="no-print mt-3"
          onsubmit="return confirm('Mark all {{ $ids->count() }} sticker(s) as printed?')">
        @csrf
        @foreach ($ids as $id)
            <input type="hidden" name="ids[]" value="{{ $id }}">
        @endforeach
        <button type="submit" class="btn btn-success">Mark these as printed</button>
        <span class="text-muted small ms-2">Press this after the stickers have printed well.</span>
    </form>
@endif
@endsection

@section('scripts')
<script src="{{ asset('js/JsBarcode.all.min.js') }}"></script>
<script>
    document.querySelectorAll('svg.barcode').forEach(function (svg) {
        JsBarcode(svg, svg.dataset.code, {
            format: 'CODE128',
            width: 2,
            height: 50,
            displayValue: false,
        });
    });

    // Long asset tags need wider stickers: switch to 2 per row.
    document.getElementById('cols').addEventListener('change', function () {
        document.querySelectorAll('.label-grid').forEach(function (grid) {
            grid.style.gridTemplateColumns = 'repeat(' + this.value + ', 1fr)';
        }, this);
    });
</script>
@endsection