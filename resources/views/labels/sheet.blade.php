@extends('layouts.app')

@section('title', 'Sticker Sheet')

@section('content')
<style>
    .label-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6mm; }
    .label { border: 1px dashed #999; padding: 3mm; text-align: center; break-inside: avoid; background: #fff; }
    .label .org { font-size: 10px; letter-spacing: 1px; color: #444; }
    .label svg { max-width: 100%; height: auto; }
    @media print {
        .navbar, .no-print, .alert { display: none !important; }
        body { background: #fff !important; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="mb-0">Sticker Sheet</h4>
        <small class="text-muted">{{ $codes->count() }} stickers
            @if ($codes->isNotEmpty()) &middot; {{ $codes->first() }} to {{ $codes->last() }} @endif
        </small>
    </div>
    <div>
        <a href="{{ route('labels.index') }}" class="btn btn-outline-secondary">Back</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print Sheet</button>
    </div>
</div>

@if ($codes->isEmpty())
    <div class="alert alert-warning">No stickers exist in that range.</div>
@else
    <div class="label-grid">
        @foreach ($codes as $code)
            <div class="label">
                <div class="org">UNBS &middot; VISITOR DEVICE</div>
                <svg class="barcode" data-code="{{ $code }}"></svg>
            </div>
        @endforeach
    </div>
@endif
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
    document.querySelectorAll('svg.barcode').forEach(function (svg) {
        JsBarcode(svg, svg.dataset.code, {
            format: 'CODE128',
            width: 2,
            height: 45,
            displayValue: true,
            fontSize: 14,
            margin: 0
        });
    });
</script>
@endsection