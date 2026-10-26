@extends('layouts.app')

@section('title', 'Visitor Devices')

@section('content')
<div class="row g-4">

    {{-- Visitor details --}}
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0">{{ $visit->name }}</h5>
                <small class="font-monospace opacity-75">{{ $visit->visit_code }}</small>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th class="text-muted fw-normal">Purpose</th>
                        <td>{{ $visit->purpose }}@if ($visit->purpose_other): {{ $visit->purpose_other }}@endif</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Visitor Card</th>
                        <td>{{ $visit->visitor_card_number }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Gate Check-In</th>
                        <td>{{ \Carbon\Carbon::parse($visit->gate_checked_in_at)->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('entry.index') }}" class="btn btn-outline-secondary btn-sm">Back to Entry</a>
            </div>
        </div>
    </div>

    {{-- Devices --}}
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0">Devices</h5>
            </div>
            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($visit->devices->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Device Code</th>
                                    <th>Description</th>
                                    <th>Serial / Asset</th>
                                    <th>Sticker</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($visit->devices as $device)
                                    <tr>
                                        <td class="font-monospace">{{ $device->device_code }}</td>
                                        <td>{{ $device->description }}</td>
                                        <td>{{ $device->asset_tag }}</td>
                                        <td class="font-monospace">{{ $device->sticker_code }}</td>
                                        <td>
                                            @if ($device->device_checked_out_at)
                                                <span class="badge bg-secondary">Out {{ \Carbon\Carbon::parse($device->device_checked_out_at)->format('H:i') }}</span>
                                            @else
                                                <span class="badge bg-success">On site</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($visit->no_device_confirmed_at)
                    <div class="alert alert-secondary mb-0">
                        Confirmed no device at {{ \Carbon\Carbon::parse($visit->no_device_confirmed_at)->format('H:i') }}.
                    </div>
                @else
                    <h6 class="mt-3">Register a device</h6>
                    <form method="POST" action="{{ route('entry.devices.store', $visit) }}" autocomplete="off">
                        @csrf

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <input type="text" id="description" name="description"
                                   class="form-control @error('description') is-invalid @enderror"
                                   value="{{ old('description') }}" placeholder="e.g. Silver MacBook Pro" autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="asset_tag" class="form-label fw-semibold">Serial / Asset Tag</label>
                            <input type="text" id="asset_tag" name="asset_tag"
                                   class="form-control @error('asset_tag') is-invalid @enderror"
                                   value="{{ old('asset_tag') }}" placeholder="Serial number or asset tag on the device">
                        </div>

                        <div class="mb-3">
                            <label for="sticker_code" class="form-label fw-semibold">Scan the sticker</label>
                            <input type="text" id="sticker_code" name="sticker_code"
                                   class="form-control form-control-lg font-monospace @error('sticker_code') is-invalid @enderror"
                                   placeholder="Stick it on the device, then scan here">
                            <div class="form-text">The scanner's Enter key saves the device.</div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save device</button>
                    </form>

                    @if ($visit->devices->isEmpty())
                        <hr>
                        <form method="POST" action="{{ route('entry.no-device', $visit) }}"
                              onsubmit="return confirm('Confirm this visitor has no device?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">No device</button>
                        </form>
                    @endif
                @endif

            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    // Enter in the first two fields moves to the next field instead of saving.
    // Only the sticker field's Enter (the scanner) submits the form.
    var order = ['description', 'asset_tag', 'sticker_code'];
    ['description', 'asset_tag'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById(order[order.indexOf(id) + 1]).focus();
            }
        });
    });
</script>
@endsection