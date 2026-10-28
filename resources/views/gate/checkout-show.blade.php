@extends('layouts.app')

@section('title', 'Visitor Check-Out')

@section('content')
@php
    $onSite = $visit->devices->whereNull('device_checked_out_at');
    $documentName = $visit->id_document_type === 'Other' && $visit->id_document_other
        ? $visit->id_document_other
        : $visit->id_document_type;
@endphp

<div class="row g-4">

    {{-- Visitor and what to hand back / collect --}}
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0">{{ $visit->name }}</h5>
                <small class="font-monospace opacity-75">{{ $visit->visit_code }}</small>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-3">
                    <tr>
                        <th class="text-muted fw-normal">Purpose</th>
                        <td>{{ $visit->purpose }}@if ($visit->purpose_other): {{ $visit->purpose_other }}@endif</td>
                    </tr>
                    <tr>
                        <th class="text-muted fw-normal">Checked In</th>
                        <td>{{ \Carbon\Carbon::parse($visit->gate_checked_in_at)->format('d M Y, H:i') }}</td>
                    </tr>
                </table>

                <div class="alert alert-warning mb-2">
                    <div class="small text-uppercase">Collect back</div>
                    <strong>Visitor card no. {{ $visit->visitor_card_number }}</strong>
                </div>
                <div class="alert alert-info mb-0">
                    <div class="small text-uppercase">Hand back</div>
                    <strong>{{ $documentName }}</strong>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('gate.checkout.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
            </div>
        </div>
    </div>

    {{-- Devices and confirmation --}}
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-danger text-white py-3">
                <h5 class="mb-0">Check-Out</h5>
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

                @if ($onSite->isNotEmpty())
                    <div class="alert alert-danger">
                        <strong>Cannot check out yet.</strong> These devices are still on site.
                        Send the visitor back to the Entry checkpoint to release them:
                        <ul class="mb-0 mt-2">
                            @foreach ($onSite as $device)
                                <li>{{ $device->description }}
                                    <span class="font-monospace">({{ $device->device_code }})</span></li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    @if ($visit->devices->isNotEmpty())
                        <div class="alert alert-success">All devices have been checked out.</div>
                    @else
                        <div class="alert alert-secondary">No devices were registered for this visitor.</div>
                    @endif

                    <form method="POST" action="{{ route('gate.checkout', $visit) }}">
                        @csrf

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="document_returned"
                                   id="document_returned" value="1" required>
                            <label class="form-check-label" for="document_returned">
                                I have returned the ID document ({{ $documentName }}) to the visitor
                            </label>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="card_collected"
                                   id="card_collected" value="1" required>
                            <label class="form-check-label" for="card_collected">
                                I have collected visitor card no. {{ $visit->visitor_card_number }}
                            </label>
                        </div>

                        <button type="submit" class="btn btn-danger btn-lg">Confirm Check-Out</button>
                    </form>
                @endif

            </div>
        </div>
    </div>

</div>
@endsection