@extends('layouts.app')

@section('title', 'Walk-in Gadget Stickers')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0">Walk-in Gadget Stickers</h4>
                <small class="opacity-75">For personal gadgets that are not in the system &middot; single use</small>
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
                    Unused: <strong>{{ $counts['unused'] ?? 0 }}</strong>
                    &middot; Used: <strong>{{ $counts['used'] ?? 0 }}</strong>
                    @if ($latest) &middot; last: <span class="font-monospace">{{ $latest->sticker_code }}</span> @endif
                </p>

                <h5>Generate a new batch</h5>
                <form method="POST" action="{{ route('labels.gadgets.generate') }}" class="row g-2 mb-4">
                    @csrf
                    <div class="col-8">
                        <input type="number" name="quantity" min="1" max="500" value="100"
                               class="form-control" aria-label="Quantity" required>
                    </div>
                    <div class="col-4 d-grid">
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </div>
                    <div class="form-text">Codes look like UNBS-WG-0001. Maximum 500 at a time.</div>
                </form>

                <hr>

                <h5>Reprint a range</h5>
                <form method="GET" action="{{ route('labels.gadgets.sheet') }}" class="row g-2 mb-4">
                    <div class="col-5">
                        <input type="number" name="from" min="1" placeholder="From no." class="form-control" required>
                    </div>
                    <div class="col-5">
                        <input type="number" name="to" min="1" placeholder="To no." class="form-control" required>
                    </div>
                    <div class="col-2 d-grid">
                        <button type="submit" class="btn btn-outline-secondary">Open</button>
                    </div>
                </form>

                <a href="{{ route('labels.gadgets.report') }}" class="btn btn-outline-dark">Walk-in gadgets report</a>
                <a href="{{ route('labels.index') }}" class="btn btn-link">Visitor stickers</a>
            </div>
        </div>

    </div>
</div>
@endsection