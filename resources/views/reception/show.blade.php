@extends('layouts.app')

@section('title', 'Reception')

@section('content')
@php
    $defaultPurpose = ($visit->purpose === 'Other' && $visit->purpose_other) ? $visit->purpose_other : $visit->purpose;
    $received = $visit->receptions->isNotEmpty();
    $allServed = $received && $visit->receptions->every(fn ($r) => (bool) $r->served);
    [$cls, $label] = ! $received ? ['bg-secondary', 'Not received'] : ($allServed ? ['bg-success', 'Served'] : ['bg-info text-dark', 'In progress']);
    $checkIn = \Carbon\Carbon::parse($visit->gate_checked_in_at);
@endphp

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h3 class="mb-0">{{ $visit->name }} <span class="badge {{ $cls }} fs-6 align-middle">{{ $label }}</span></h3>
        <div class="text-muted">
            <span class="font-monospace">{{ $visit->visit_code }}</span>
            &middot; {{ $defaultPurpose }}
            &middot; checked in {{ $checkIn->format('H:i') }} ({{ $checkIn->diffForHumans() }})
        </div>
    </div>
    <a href="{{ route('reception.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">

    <div class="col-lg-8">

        {{-- Reception records: serve from here --}}
        @if ($received)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0">Reception record</h5>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach ($visit->receptions as $record)
                        <li class="list-group-item py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">{{ $record->destination }}</div>
                                    <div class="small text-muted">
                                        Meeting {{ $record->meeting_with ?: '—' }}
                                        &middot; received by {{ $record->receivedBy->name ?? '—' }}
                                        &middot; {{ $record->created_at->format('d M, H:i') }}
                                    </div>
                                </div>
                                @if ($record->served)
                                    <div class="text-end">
                                        <span class="badge bg-success">Served</span>
                                        @if ($record->served_at)
                                            <div class="small text-muted">{{ \Carbon\Carbon::parse($record->served_at)->format('d M, H:i') }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            @if ($record->served)
                                @if ($record->note)
                                    <div class="small mt-2">{{ $record->note }}</div>
                                @endif
                            @else
                                <form method="POST" action="{{ route('reception.served', $record) }}" class="d-flex gap-2 mt-3">
                                    @csrf
                                    <input type="text" name="note" maxlength="500" class="form-control"
                                           placeholder="Note (optional)">
                                    <button type="submit" class="btn btn-success text-nowrap">Mark served</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Receive form: open when the visitor has not been received, folded away afterwards --}}
        <details class="card shadow-sm border-0" @if (! $received || $errors->any()) open @endif>
            <summary class="card-header bg-primary text-white py-3 fw-semibold" style="cursor: pointer;">
                {{ $received ? 'Receive at another destination' : 'Receive visitor' }}
            </summary>
            <div class="card-body">
                <form method="POST" action="{{ route('reception.visits.store', $visit) }}">
                    @csrf

                    <div class="mb-3">
                        <label for="destination" class="form-label">Destination (department / office)</label>
                        <input type="text" id="destination" name="destination" value="{{ old('destination') }}"
                               class="form-control" required @if (! $received) autofocus @endif>
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
        </details>
    </div>

    {{-- Visitor details, kept small --}}
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3"><h6 class="mb-0">Visitor</h6></div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <th class="text-muted fw-normal">Mobile</th>
                        <td>
                            @if ($visit->visitor_mobile_number)
                                <a href="tel:{{ $visit->visitor_mobile_number }}">{{ $visit->visitor_mobile_number }}</a>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Card no.</th>
                        <td>{{ $visit->visitor_card_number }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Devices</th>
                        <td>
                            @forelse ($visit->devices as $device)
                                <div>
                                    {{ $device->description }}
                                    @if ($device->device_checked_out_at)
                                        <span class="badge bg-secondary">Out</span>
                                    @else
                                        <span class="badge bg-success">On site</span>
                                    @endif
                                </div>
                            @empty
                                <span class="text-muted">None</span>
                            @endforelse
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection