@extends('layouts.app')

@section('title', 'Device Check-Out')

@section('content')
@php $onSite = $visit->devices->whereNull('device_checked_out_at'); @endphp

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

    {{-- Device check-out --}}
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-danger text-white py-3">
                <h5 class="mb-0">Device Check-Out</h5>
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
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($visit->devices as $device)
                                    <tr>
                                        <td class="font-monospace">{{ $device->device_code }}</td>
                                        <td>{{ $device->description }}</td>
                                        <td>{{ $device->asset_tag }}</td>
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

                @if ($visit->devices->isEmpty())
                    <div class="alert alert-info mb-0">No devices were registered for this visitor.</div>
                @elseif ($onSite->isEmpty())
                    <div class="alert alert-success mb-0">
                        All devices checked out. The visitor may now proceed to the Gate for check-out.
                    </div>
                @else
                    <form method="POST" action="{{ route('entry.visits.checkout.device', $visit) }}" autocomplete="off">
                        @csrf
                        <label for="sticker_code" class="form-label fw-semibold">Scan the sticker on the device being released</label>
                        <input type="text" id="sticker_code" name="sticker_code"
                               class="form-control form-control-lg font-monospace @error('sticker_code') is-invalid @enderror"
                               placeholder="Scan sticker here" autofocus>
                        <div class="form-text">The scanner's Enter key confirms the check-out.</div>
                    </form>
                @endif

            </div>
        </div>
    </div>

</div>
@endsection