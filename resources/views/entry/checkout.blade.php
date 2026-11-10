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
                    <tr>
                        <th class="text-muted fw-normal">{{ $area }}</th>
                        <td>
                            @if ($served)
                                <span class="badge bg-success">Served</span>
                            @else
                                <span class="badge bg-warning text-dark">Not served</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('entry.index') }}" class="btn btn-outline-secondary btn-sm">Back to Entry</a>
            </div>
        </div>
    </div>

    {{-- Device check-out and clearance --}}
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

                @if ($visit->entry_cleared_at)

                    <div class="alert alert-success mb-0">
                        <strong>Cleared for exit</strong> at {{ \Carbon\Carbon::parse($visit->entry_cleared_at)->format('H:i') }}.
                        Send the visitor to the Gate to check out.
                    </div>

                @elseif (! $approved)

                    <div class="alert alert-warning">
                        <strong>Not served.</strong> This visitor has not been served by {{ $area }}.
                        The devices stay with the visitor until you decide.
                        @if ($sentBackAt)
                            <div class="small mt-1">Already sent back once at {{ $sentBackAt->format('H:i') }}.</div>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('entry.visits.send-back', $visit) }}" class="mb-3">
                        @csrf
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Send back to {{ $area }}</button>
                        </div>
                    </form>

                    <details @if ($errors->any()) open @endif class="border rounded p-3">
                        <summary class="fw-semibold">Visitor must leave: record why they were not served</summary>

                        <form method="POST" action="{{ route('entry.visits.unserved', $visit) }}" class="mt-3">
                            @csrf

                            <div class="mb-3">
                                <label for="reason" class="form-label fw-semibold">Reason</label>
                                <select id="reason" name="reason" class="form-select @error('reason') is-invalid @enderror">
                                    <option value="">Select a reason...</option>
                                    @foreach ($reasons as $reason)
                                        <option value="{{ $reason->name }}" @selected(old('reason') === $reason->name)>{{ $reason->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input @error('phone_confirmed') is-invalid @enderror" type="checkbox"
                                       name="phone_confirmed" value="1" id="phone_confirmed" @checked(old('phone_confirmed'))>
                                <label class="form-check-label" for="phone_confirmed">
                                    I contacted {{ $area }} and confirmed this visitor has not reached them.
                                    <span class="d-block text-muted small">This makes sure no one leaves unserved for a reason that can be solved.</span>
                                </label>
                            </div>

                            <div class="mb-3">
                                <label for="confirmed_with" class="form-label fw-semibold">Who did you speak to?</label>
                                <select id="confirmed_with" name="confirmed_with"
                                        class="form-select @error('confirmed_with') is-invalid @enderror">
                                    <option value="">Select a receptionist...</option>
                                    @foreach ($receptionists as $name)
                                        <option value="{{ $name }}" @selected(old('confirmed_with') === $name)>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @if (empty($receptionists))
                                    <div class="form-text text-danger">No receptionist accounts were found for {{ $area }}. Ask the administrator.</div>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label for="note" class="form-label fw-semibold">
                                    Note <span class="text-muted fw-normal">(required for "Other")</span>
                                </label>
                                <textarea id="note" name="note" rows="2" maxlength="500"
                                          class="form-control @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger">Record reason, then release devices</button>
                            </div>
                        </form>
                    </details>

                @elseif ($onSite->isNotEmpty())

                    @if ($leftUnserved)
                        <div class="alert alert-warning py-2">
                            Leaving without being served. Reason recorded: <strong>{{ $leftUnserved->reason }}</strong>.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('entry.visits.checkout.device', $visit) }}" autocomplete="off">
                        @csrf
                        <label for="sticker_code" class="form-label fw-semibold">Scan the sticker on the device being released</label>
                        <input type="text" id="sticker_code" name="sticker_code"
                               class="form-control form-control-lg font-monospace @error('sticker_code') is-invalid @enderror"
                               placeholder="Scan sticker here" autofocus>
                        <div class="form-text">The scanner's Enter key confirms the check-out.</div>
                    </form>

                @else

                    <div class="alert alert-info">
                        @if ($visit->devices->isEmpty())
                            No devices were registered for this visitor.
                        @else
                            All devices checked out.
                        @endif
                    </div>

                    @if ($leftUnserved)
                        <div class="alert alert-warning py-2">
                            Leaving without being served. Reason recorded: <strong>{{ $leftUnserved->reason }}</strong>.
                        </div>
                    @else
                        <div class="alert alert-success py-2">Served by {{ $area }}.</div>
                    @endif

                    <form method="POST" action="{{ route('entry.visits.clear', $visit) }}">
                        @csrf
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">Clear for Gate exit</button>
                        </div>
                    </form>

                @endif
            </div>
        </div>
    </div>

</div>
@endsection