@extends('layouts.app')

@section('title', 'Barcode Labels')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0">Barcode Labels</h4>
                <small class="opacity-75">Visitor device stickers &middot; single use</small>
            </div>

            <div class="card-body p-4">

                @if ($errors->any())
                    <div class="alert alert-danger mb-3">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="text-muted mb-4">
                    Stickers generated so far: <strong>{{ $total }}</strong>
                    @if ($latest) &middot; last: <span class="font-monospace">{{ $latest->sticker_code }}</span> @endif
                </p>

                <h5>Generate a new batch</h5>
                <form method="POST" action="{{ route('labels.stickers.generate') }}" class="row g-2 mb-4">
                    @csrf
                    <div class="col-8">
                        <input type="number" name="quantity" min="1" max="500" value="100"
                               class="form-control" aria-label="Quantity" required>
                    </div>
                    <div class="col-4 d-grid">
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </div>
                    <div class="form-text">Numbers continue from the last batch. Maximum 500 at a time.</div>
                </form>

                <hr>

                <h5>Reprint a range</h5>
                <form method="GET" action="{{ route('labels.stickers.sheet') }}" class="row g-2">
                    <div class="col-5">
                        <input type="number" name="from" min="1" placeholder="From no. (e.g. 1)" class="form-control" required>
                    </div>
                    <div class="col-5">
                        <input type="number" name="to" min="1" placeholder="To no. (e.g. 100)" class="form-control" required>
                    </div>
                    <div class="col-2 d-grid">
                        <button type="submit" class="btn btn-outline-secondary">Open</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection