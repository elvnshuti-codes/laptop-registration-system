@extends('layouts.app')

@section('title', 'Entry Checkpoint')

@section('content')
<div class="row g-4">

    {{-- Visitors --}}
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0">Visitor</h4>
                <small class="opacity-75">Checkpoint 2 &middot; Main entrance</small>
            </div>
            <div class="card-body p-4">
                <h5>Check-in</h5>
                <p class="text-muted">Scan the barcode on the visitor's gate ticket, or type the visit code.</p>

                <form method="GET" action="{{ route('entry.lookup') }}">
                    <div class="input-group input-group-lg">
                        <input type="text" name="code" value="{{ old('code') }}"
                               class="form-control @error('code') is-invalid @enderror"
                               placeholder="Scan or type visit code" autocomplete="off" autofocus required>
                        <button type="submit" class="btn btn-primary">Find visitor</button>
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </form>

                <hr class="my-4">

                <h5>Check-out</h5>
                <p class="text-muted">Scan the visitor's gate ticket, then scan each device's sticker to release it.</p>

                <form method="GET" action="{{ route('entry.checkout.lookup') }}">
                    <div class="input-group input-group-lg">
                        <input type="text" name="checkout_code" value="{{ old('checkout_code') }}"
                               class="form-control @error('checkout_code') is-invalid @enderror"
                               placeholder="Scan or type visit code" autocomplete="off" required>
                        <button type="submit" class="btn btn-danger">Find visitor</button>
                        @error('checkout_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Staff --}}
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-secondary text-white py-3">
                <h4 class="mb-0">Staff</h4>
                <small class="opacity-75">Checkpoint 2 &middot; Main entrance</small>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-0">Staff laptop check-in and check-out will be added in a later stage.</p>
            </div>
        </div>
    </div>

</div>
@endsection