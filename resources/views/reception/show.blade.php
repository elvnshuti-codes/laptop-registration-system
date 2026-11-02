@extends('layouts.app')

@section('title', 'Reception')

@section('content')
@php
    $onSite = $visit->devices->whereNull('device_checked_out_at');
    $defaultPurpose = ($visit->purpose === 'Other' && $visit->purpose_other) ? $visit->purpose_other : $visit->purpose;
@endphp

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
                        <td>{{ $defaultPurpose }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Visitor card</th>
                        <td>{{ $visit->visitor_card_number }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Checked in</th>
                        <td>{{ \Carbon\Carbon::parse($visit->gate_checked_in_at)->format('d M Y, H:i') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Devices</th>
                        <td>
                            @if ($visit->devices->isEmpty())
                                <span class="text-muted">None registered</span>
                            @else
                                @foreach ($visit->devices as $device)
                                    <div>{{ $device->description }}
                                        @if ($device->device_checked_out_at)
                                            <span class="badge bg-secondary">Out</span>
                                        @else
                                            <span class="badge bg-success">On site</span>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('reception.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
            </div>
        </div>
    </div>

    <div class="col-lg-7">

        {{-- Receive form --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0">Receive visitor</h5>
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

                <form method="POST" action="{{ route('reception.visits.store', $visit) }}">
                    @csrf

                    <div class="mb-3">
                        <label for="destination" class="form-label">Destination (department / office)</label>
                        <input type="text" id="destination" name="destination" value="{{ old('destination') }}"
                               class="form-control" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="meeting_with" class="form-label">Meeting with <span class="text-muted">(optional)</span></label>
                        <input type="text" id="meeting_with" name="meeting_with" value="{{ old('meeting_with') }}"
                               class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="purpose_confirmed" class="form-label">Purpose (confirmed with the visitor)</label>
                        <input type="text" id="purpose_confirmed" name="purpose_confirmed"
                               value="{{ old('purpose_confirmed', $defaultPurpose) }}"
                               class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>

        {{-- Reception records --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Reception record</h5>
            </div>
            <div class="card-body">
                @if ($visit->receptions->isEmpty())
                    <p class="text-muted mb-0">This visitor has not been received yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Destination</th>
                                    <th>Meeting with</th>
                                    <th>Received by</th>
                                    <th>Time</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($visit->receptions as $record)
                                    <tr>
                                        <td>{{ $record->destination }}</td>
                                        <td>{{ $record->meeting_with ?? '—' }}</td>
                                        <td>{{ $record->receivedBy->name ?? '—' }}</td>
                                        <td>{{ $record->created_at->format('d M, H:i') }}</td>
                                        <td class="text-end">
                                            @if ($record->served)
                                                <span class="badge bg-success">Served</span>
                                            @else
                                                <form method="POST" action="{{ route('reception.served', $record) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">Mark served</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection