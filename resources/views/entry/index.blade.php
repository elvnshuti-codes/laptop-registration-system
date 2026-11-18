@extends('layouts.app')

@section('title', 'Entry Checkpoint')

@section('content')
@php
    $panel = $errors->has('checkout_code') ? 'out' : 'in';
    $canStaff = auth()->user()->can('manage staff entry');
@endphp

<style>
    .tile {
        display: flex; flex-direction: column; justify-content: space-between;
        width: 100%; min-height: clamp(120px, 22vh, 220px); padding: 1.25rem; border: 0; border-radius: 1rem;
        color: #fff; text-align: left; text-decoration: none;
        box-shadow: 0 .25rem .75rem rgba(0, 0, 0, .12);
        transition: transform .08s ease;
    }
    .tile:hover { color: #fff; }
    .tile.bg-warning, .tile.bg-warning:hover { color: #212529; }
    .tile:active { transform: scale(.98); }
    .tile.active { outline: 5px solid #ffc107; outline-offset: 2px; }
    .tile .tile-title { font-size: clamp(1.2rem, 1.6vw, 1.8rem); font-weight: 700; line-height: 1.2; }
    .tile .tile-hint { opacity: .85; }
    .tile .tile-count { font-size: 2.25rem; font-weight: 700; line-height: 1; }
    .tile.disabled-tile { background: #6c757d; opacity: .55; pointer-events: none; }
</style>

<div class="mb-3">
    <h3 class="mb-0">Entry checkpoint</h3>
    <div class="text-muted">Main entrance &middot; choose what you are doing</div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <button type="button" class="tile bg-primary {{ $panel === 'in' ? 'active' : '' }}" data-panel="in">
            <div>
                <div class="tile-title">Visitor check-in</div>
                <div class="tile-hint">Scan the gate ticket</div>
            </div>
        </button>
    </div>

    <div class="col-6 col-lg-3">
        <button type="button" class="tile bg-danger {{ $panel === 'out' ? 'active' : '' }}" data-panel="out">
            <div>
                <div class="tile-title">Visitor check-out</div>
                <div class="tile-hint">Release devices</div>
            </div>
        </button>
    </div>

    @if ($canStaff)
        <div class="col-6 col-lg-3">
            <a href="{{ route('entry.staff.index') }}" class="tile bg-success">
                <div>
                    <div class="tile-title">Staff check-in</div>
                    <div class="tile-hint">Laptops and gadgets</div>
                </div>
                <div class="tile-count">{{ $staffInside }} <span class="fs-6 fw-normal">inside</span></div>
            </a>
        </div>

        <div class="col-6 col-lg-3">
            <a href="{{ route('entry.staff-out.start') }}" class="tile bg-warning">
                <div>
                    <div class="tile-title">Staff check-out</div>
                    <div class="tile-hint">Scan the staff ticket</div>
                </div>
                <div class="tile-count">{{ $staffInside }} <span class="fs-6 fw-normal">inside</span></div>
            </a>
        </div>
    @endif
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">

        {{-- Visitor check-in --}}
        <div id="panel-in" class="{{ $panel === 'in' ? '' : 'd-none' }}">
            <h4>Visitor check-in</h4>
            <p class="text-muted">Scan the barcode on the visitor's gate ticket, or type the visit code.</p>

            <form method="GET" action="{{ route('entry.lookup') }}">
                <div class="input-group input-group-lg">
                    <input type="text" name="code" value="{{ old('code') }}" id="input-in"
                           class="form-control @error('code') is-invalid @enderror"
                           placeholder="Scan or type visit code" autocomplete="off" required>
                    <button type="submit" class="btn btn-primary">Find visitor</button>
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </form>
        </div>

        {{-- Visitor check-out --}}
        <div id="panel-out" class="{{ $panel === 'out' ? '' : 'd-none' }}">
            <h4>Visitor check-out</h4>
            <p class="text-muted">Scan the visitor's gate ticket, then scan each device's sticker to release it.</p>

            <form method="GET" action="{{ route('entry.checkout.lookup') }}">
                <div class="input-group input-group-lg">
                    <input type="text" name="checkout_code" value="{{ old('checkout_code') }}" id="input-out"
                           class="form-control @error('checkout_code') is-invalid @enderror"
                           placeholder="Scan or type visit code" autocomplete="off" required>
                    <button type="submit" class="btn btn-danger">Find visitor</button>
                    @error('checkout_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        let current = @json($panel);

        function show(name) {
            current = name;

            ['in', 'out'].forEach(function (key) {
                document.getElementById('panel-' + key).classList.toggle('d-none', key !== name);
            });

            document.querySelectorAll('.tile[data-panel]').forEach(function (tile) {
                tile.classList.toggle('active', tile.dataset.panel === name);
            });

            document.getElementById('input-' + name).focus();
        }

        document.querySelectorAll('.tile[data-panel]').forEach(function (tile) {
            tile.addEventListener('click', function () { show(tile.dataset.panel); });
        });

        // A scanner types into whatever has focus: if nothing has, send the keys to the open scan box.
        document.addEventListener('keydown', function (e) {
            if (document.activeElement === document.body && e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                document.getElementById('input-' + current).focus();
            }
        });

        show(current);
    })();
</script>
@endsection