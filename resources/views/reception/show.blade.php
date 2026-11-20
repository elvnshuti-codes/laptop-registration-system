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

        {{-- Visit details: recorded here in full (the Gate only keeps a short note) --}}
        @php
            $detail = $visit->detail;
            $detailsOpen = ! $detail || $errors->hasAny(['details', 'department', 'person_to_see', 'delivery_to', 'delivery_company']);
        @endphp

        <div class="card shadow-sm border-0 mb-4 {{ $detail ? '' : 'border border-warning' }}">
            <div class="card-header {{ $detail ? 'bg-white' : 'bg-warning' }} py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $isDelivery ? 'Delivery details' : 'Visit details' }}</h5>
                @if ($detail)
                    <span class="badge bg-success">Recorded</span>
                @else
                    <span class="badge bg-dark">Required before receiving</span>
                @endif
            </div>
            <div class="card-body">

                @if ($visit->gate_note)
                    <div class="alert alert-secondary py-2 small">
                        <strong>Note from the Gate:</strong> {{ $visit->gate_note }}
                    </div>
                @endif

                @if ($detail)
                    <table class="table table-borderless table-sm mb-2">
                        @if ($isDelivery)
                            <tr><th class="text-muted fw-normal w-25">Delivering to</th><td>{{ $deliveryTo[$visit->delivery_to] ?? ($detail->person_to_see ? 'An individual' : '—') }}</td></tr>
                            <tr><th class="text-muted fw-normal">Items</th><td>{{ $detail->details }}</td></tr>
                            <tr><th class="text-muted fw-normal">Company</th><td>{{ $detail->delivery_company ?: '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">For</th><td>{{ $detail->person_to_see ?: '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">Department</th><td>{{ $detail->department ?: '—' }}</td></tr>
                        @else
                            <tr><th class="text-muted fw-normal w-25">Department</th><td>{{ $detail->department ?: '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">Person to see</th><td>{{ $detail->person_to_see ?: '—' }}</td></tr>
                            <tr><th class="text-muted fw-normal">Details</th><td>{{ $detail->details }}</td></tr>
                        @endif
                    </table>
                    <div class="small text-muted mb-2">Recorded by {{ $detail->recorder?->name ?? '—' }}, {{ $detail->updated_at->format('d M, H:i') }}</div>
                @endif

                <details @if ($detailsOpen) open @endif>
                    <summary class="fw-semibold" style="cursor: pointer;">{{ $detail ? 'Change the details' : 'Record the details' }}</summary>

                    <form method="POST" action="{{ route('reception.visits.intake', $visit) }}" class="mt-3" id="intake-form">
                        @csrf

                        @if ($isDelivery)
                            <div class="mb-3">
                                <label for="delivery_to" class="form-label">Delivering to</label>
                                <select id="delivery_to" name="delivery_to" class="form-select" required>
                                    <option value="">Choose...</option>
                                    @foreach ($deliveryTo as $key => $text)
                                        <option value="{{ $key }}" @selected(old('delivery_to', $visit->delivery_to) === $key)>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="details" class="form-label">What is being delivered?</label>
                                <textarea id="details" name="details" rows="2" maxlength="500" class="form-control" required>{{ old('details', $detail?->details) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label for="delivery_company" class="form-label">Company making the delivery</label>
                                <input type="text" id="delivery_company" name="delivery_company" maxlength="255" class="form-control"
                                       value="{{ old('delivery_company', $detail?->delivery_company) }}" required>
                            </div>

                            <div id="individual-fields">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="person_to_see" class="form-label">Delivered to (name)</label>
                                        <input type="text" id="person_to_see" name="person_to_see" maxlength="255" class="form-control"
                                               value="{{ old('person_to_see', $detail?->person_to_see === 'UNBS Stores' ? '' : $detail?->person_to_see) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="department" class="form-label">Department</label>
                                        <select id="department" name="department" class="form-select">
                                            <option value="">Choose...</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department }}" @selected(old('department', $detail?->department) === $department)>{{ $department }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="department" class="form-label">Department <span class="text-muted">(optional)</span></label>
                                    <select id="department" name="department" class="form-select">
                                        <option value="">Choose...</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department }}" @selected(old('department', $detail?->department) === $department)>{{ $department }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="person_to_see" class="form-label">Person to see <span class="text-muted">(optional)</span></label>
                                    <input type="text" id="person_to_see" name="person_to_see" maxlength="255" class="form-control"
                                           value="{{ old('person_to_see', $detail?->person_to_see) }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="details" class="form-label">Details of the visit</label>
                                <textarea id="details" name="details" rows="2" maxlength="500" class="form-control" required
                                          placeholder="e.g. Attending SACCO manager interviews">{{ old('details', $detail?->details) }}</textarea>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary">Save details</button>
                    </form>
                </details>
            </div>
        </div>

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
                                        &middot; received by {{ $record->receivedBy->name ??'—' }}
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

        {{-- Receive: one button, built from the details above --}}
        @if (! $visit->detail)
            <div class="alert alert-warning">Record the visit details above first, then receive the visitor.</div>
        @elseif (! $received)
            <form method="POST" action="{{ route('reception.visits.store', $visit) }}">
                @csrf
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Receive visitor</button>
                </div>
                <div class="form-text text-center mt-2">
                    Goes to {{ $visit->detail->department ?: ($visit->detail->person_to_see ?: 'Standard House') }}
                </div>
            </form>
        @else
            <details class="card shadow-sm border-0" @if ($errors->has('destination')) open @endif>
                <summary class="card-header bg-white py-3 fw-semibold" style="cursor: pointer;">Also received at another destination</summary>
                <div class="card-body">
                    <form method="POST" action="{{ route('reception.visits.store', $visit) }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="destination" maxlength="255" class="form-control"
                               placeholder="Department / office" required>
                        <button type="submit" class="btn btn-primary text-nowrap">Receive</button>
                    </form>
                </div>
            </details>
        @endif
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

@if ($isDelivery)
@section('scripts')
<script>
    (function () {
        const to = document.getElementById('delivery_to');
        const box = document.getElementById('individual-fields');
        const name = document.getElementById('person_to_see');
        const dept = document.getElementById('department');
        if (!to) return;

        function update() {
            const individual = to.value === 'individual';
            box.style.display = to.value === 'stores' ? 'none' : '';
            name.required = individual;
            dept.required = individual;
        }
        to.addEventListener('change', update);
        update();
    })();
</script>
@endsection
@endif