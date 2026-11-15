@extends('layouts.app')

@section('title', 'Main Gate')

@section('content')
@php
    // Which scan box to show: the one that just failed, otherwise check-out.
    $panel = $errors->has('booking_code') ? 'booking' : 'out';
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
    .tile:active { transform: scale(.98); }
    .tile.active { outline: 5px solid #ffc107; outline-offset: 2px; }
    .tile .tile-title { font-size: clamp(1.2rem, 1.6vw, 1.8rem); font-weight: 700; line-height: 1.2; }
    .tile .tile-hint { opacity: .85; }
</style>

<div class="mb-3">
    <h3 class="mb-0">Main gate</h3>
    <div class="text-muted">Checkpoint 1 &middot; choose what you are doing</div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <a href="{{ route('gate.create') }}" class="tile bg-primary">
            <div>
                <div class="tile-title">New visitor</div>
                <div class="tile-hint">Check in at the gate</div>
            </div>
        </a>
    </div>

    <div class="col-6 col-lg-3">
        <button type="button" class="tile {{ $panel === 'booking' ? 'active' : '' }}" style="background: #0a7c86" data-panel="booking">
            <div>
                <div class="tile-title">Visitor with booking</div>
                <div class="tile-hint">Scan the booking code</div>
            </div>
        </button>
    </div>

    <div class="col-6 col-lg-3">
        <button type="button" class="tile bg-danger {{ $panel === 'out' ? 'active' : '' }}" data-panel="out">
            <div>
                <div class="tile-title">Visitor check-out</div>
                <div class="tile-hint">Scan the visit ticket</div>
            </div>
        </button>
    </div>

    <div class="col-6 col-lg-3">
        <a href="{{ route('gate.checkout.index') }}" class="tile bg-dark">
            <div>
                <div class="tile-title">Visitors on site</div>
                <div class="tile-hint">See who is still inside</div>
            </div>
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">

        {{-- Booking --}}
        <div id="panel-booking" class="{{ $panel === 'booking' ? '' : 'd-none' }}">
            <h4>Visitor with a booking</h4>
            <p class="text-muted">Scan or type the booking code. The visitor's details are filled in for you.</p>

            <form method="GET" action="{{ route('gate.booking.lookup') }}">
                <div class="input-group input-group-lg">
                    <input type="text" name="booking_code" value="{{ old('booking_code') }}" id="input-booking"
                           class="form-control text-uppercase @error('booking_code') is-invalid @enderror"
                           placeholder="BK-XXXXXX" autocomplete="off" required>
                    <button type="submit" class="btn btn-primary">Find booking</button>
                    @error('booking_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </form>
        </div>

        {{-- Check-out --}}
        <div id="panel-out" class="{{ $panel === 'out' ? '' : 'd-none' }}">
            <h4>Visitor check-out</h4>
            <p class="text-muted">Scan the barcode on the visitor's ticket, or type the visit code.</p>

            <form method="GET" action="{{ route('gate.checkout.lookup') }}">
                <div class="input-group input-group-lg">
                    <input type="text" name="code" value="{{ old('code') }}" id="input-out"
                           class="form-control @error('code') is-invalid @enderror"
                           placeholder="Scan or type visit code" autocomplete="off" required>
                    <button type="submit" class="btn btn-danger">Find visitor</button>
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
        const names = ['booking', 'out'];

        function show(name) {
            current = name;

            names.forEach(function (key) {
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