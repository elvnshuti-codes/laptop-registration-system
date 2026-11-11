@extends('layouts.app')

@section('title', 'Visitor Record')

@section('content')
@php
    $u = $info['unserved'];
    $badge = match ($info['status']) {
        'Served' => 'bg-success',
        'Left unserved' => 'bg-danger',
        'Waiting' => 'bg-warning text-dark',
        default => 'bg-secondary',
    };

    $servedBy = $visit->for_sample_reception
        ? $visit->sampleReception?->servedBy?->name
        : $visit->receptions->filter(fn ($r) => (bool) $r->served)->sortBy('served_at')->first()?->receivedBy?->name;

    $steps = [
        ['label' => 'Checked in at the Gate', 'at' => $info['checkIn'], 'note' => null],
        ['label' => $visit->devices->isNotEmpty() ? 'Entry: device registered' : 'Entry: no device confirmed', 'at' => $info['entryAt'], 'note' => null],
    ];

    if (! $visit->for_sample_reception) {
        $steps[] = [
            'label' => 'Received at Reception',
            'at' => $info['receivedAt'],
            'note' => trim($info['destinations'] . ($info['meeting'] ? ' · meeting ' . $info['meeting'] : '')) ?: null,
        ];
    }

    $steps[] = ['label' => 'Served', 'at' => $info['servedAt'], 'note' => $servedBy ? 'by ' . $servedBy : null];
    $steps[] = ['label' => 'Cleared at Entry for exit', 'at' => $info['clearedAt'], 'note' => $clearedBy ? 'by ' . $clearedBy : null];
    $steps[] = ['label' => 'Checked out at the Gate', 'at' => $info['checkOut'], 'note' => null];

    $prev = null;
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">{{ $visit->name }} <span class="badge {{ $badge }} fs-6 align-middle">{{ $info['status'] }}</span></h3>
        <div class="text-muted font-monospace">{{ $visit->visit_code }}</div>
    </div>
    <a href="{{ url()->previous() === url()->current() ? route('records.index') : url()->previous() }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>

@if ($u)
    <div class="alert alert-danger">
        <div class="fw-bold mb-1">Left without being served: follow up</div>
        <div>
            Reason: <strong>{{ $u->reason }}</strong>@if ($u->note) &mdash; {{ $u->note }}@endif
        </div>
        <div>
            Mobile:
            @if ($visit->visitor_mobile_number)
                <a href="tel:{{ $visit->visitor_mobile_number }}" class="fw-semibold">{{ $visit->visitor_mobile_number }}</a>
            @else
                <span class="text-muted">none recorded</span>
            @endif
        </div>
        <div class="small mt-1">
            Recorded by {{ $u->recordedBy?->name ?? 'unknown' }} at {{ $u->created_at->format('d M, H:i') }}.
            Reception contacted: {{ $u->phone_confirmed ? 'yes' : 'no' }}@if ($u->confirmed_with), spoke to {{ $u->confirmed_with }}@endif.
            @if ($u->acknowledged_at)
                Acknowledged by {{ $u->acknowledgedBy?->name ?? 'unknown' }} at {{ \Carbon\Carbon::pare($u->acknowledged_at)->format('d M, H:i') }}.
            @else
                <strong>Not yet acknowledged.</strong>
            @endif
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-3"><h5 class="mb-0">Visitor</h5></div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><th class="text-muted fw-normal">Purpose</th><td>{{ $visit->purpose }}@if ($visit->purpose_other): {{ $visit->purpose_other }}@endif</td></tr>
                    <tr>
                        <th class="text-muted fw-normal">ID</th>
                        <td>{{ $visit->id_document_type === 'Other' ? $visit->id_document_other : $visit->id_document_type }} {{ $visit->id_document_number }}</td>
                    </tr>
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
                    <tr><th class="text-muted fw-normal">Visitor card</th><td>{{ $visit->visitor_card_number }}</td></tr>
                    <tr><th class="text-muted fw-normal">Area</th><td>{{ $info['area'] }}</td></tr>
                    <tr><th class="text-muted fw-normal">Destination</th><td>{{ $info['destinations'] ?: '—' }}</td></tr>
                    <tr><th class="text-muted fw-normal">Meeting with</th><td>{{ $info['meeting'] ?: '—' }}</td></tr>
                    <tr><th class="text-muted fw-normal">Time on premises</th><td class="fw-semibold">{{ $info['duration'] }}</td></tr>
                </table>
            </div>
        </div>

        @if ($visit->devices->isNotEmpty())
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white py-3"><h5 class="mb-0">Devices</h5></div>
                <ul class="list-group list-group-flush">
                    @foreach ($visit->devices as $device)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $device->description }} <span class="text-muted small font-monospace">{{ $device->device_code }}</span></span>
                            <span class="small text-muted">
                                in {{ \Carbon\Carbon::parse($device->device_checked_in_at)->format('H:i') }}@if ($device->device_checked_out_at), out {{ \Carbon\Carbon::parse($device->device_checked_out_at)->format('H:i') }}@endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($visit->for_sample_reception)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white py-3"><h5 class="mb-0">Samples</h5></div>
                <div class="card-body">
                    <table class="table table-borderless mb-2">
                        <tr><th class="text-muted fw-normal">Owner's company</th><td>{{ $visit->sample_owner_company }}</td></tr>
                        <tr><th class="text-muted fw-normal">Owner</th><td>{{ $visit->sample_owner_name ?: '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal">PRN</th><td>{{ $visit->sample_prn ?: '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Delivered by</th><td>{{ $visit->delivered_by_type }}@if ($visit->delivered_by_company) ({{ $visit->delivered_by_company }})@endif</td></tr>
                        <tr><th class="text-muted fw-normal">Reference no.</th><td>{{ $visit->sampleReception?->reference_number ?: '—' }}</td></tr>
                    </table>
                    @foreach ($visit->samples as $sample)
                        <div class="small">{{ $sample->sample_type }} &times; {{ $sample->sample_count }}</div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-3"><h5 class="mb-0">Timeline</h5></div>
            <ul class="list-group list-group-flush">
                @foreach ($steps as $s)
                    <li class="list-group-item d-flex justify-content-between">
                        <div>
                            <div class="fw-semibold @if (! $s['at']) text-muted @endif">{{ $s['label'] }}</div>
                            @if ($s['note'])
                                <div class="small text-muted">{{ $s['note'] }}</div>
                            @endif
                        </div>
                        <div class="text-end">
                            @if ($s['at'])
                                <div>{{ $s['at']->format('d M Y, H:i') }}</div>
                                @if ($prev)
                                    <div class="small text-muted">+{{ (int) abs($prev->diffInMinutes($s['at'])) }} min</div>
                                @endif
                                @php $prev = $s['at']; @endphp
                            @else
                                <span class="small text-muted">Not recorded</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($visit->exceptions->isNotEmpty())
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white py-3"><h5 class="mb-0">Exceptions</h5></div>
                <ul class="list-group list-group-flush">
                    @foreach ($visit->exceptions->sortBy('created_at') as $ex)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">{{ $exceptionLabels[$ex->type] ?? $ex->type }}</span>
                                <span class="small text-muted">{{ $ex->created_at->format('d M, H:i') }} &middot; {{ $ex->recordedBy?->name ?? 'unknown' }}</span>
                            </div>
                            @if ($ex->reason)
                                <div class="small">{{ $ex->reason }}@if ($ex->note) &mdash; {{ $ex->note }}@endif</div>
                            @endif
                            @if ($ex->type === 'left_unserved')
                                <div class="small text-muted">
                                    Reception contacted: {{ $ex->phone_confirmed ? 'yes' : 'no' }}@if ($ex->confirmed_with), spoke to {{ $ex->confirmed_with }}@endif.
                                    @if ($ex->acknowledged_at)
                                        ledged by {{ $ex->acknowledgedBy?->name ?? 'unknown' }}.
                                    @else
                                        Not yet acknowledged.
                                    @endif
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection
