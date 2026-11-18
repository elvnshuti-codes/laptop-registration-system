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
                    @if ($visit->arrival)
                        <tr>
                            <th class="text-muted fw-normal">Arrived by</th>
                            <td>{{ $visit->arrival->label() }}</td>
                        </tr>
                    @endif
                </table>

                <div class="alert alert-warning mb-2">
                    <div class="small text-uppercase">Collect back</div>
                    <strong>Visitor card no. {{ $visit->visitor_card_number }}</strong>
                </div>
                <div class="alert alert-info mb-0">
                    <div class="small text-uppercase">Hand back</div>
                    <strong>{{ $documentName }}</strong>
                </div>
                @if ($visit->leftItems->isNotEmpty())
                    <div class="alert alert-secondary mt-2 mb-0">
                        <div class="small text-uppercase">Belongings left at the gate</div>
                        <ul class="mb-1 ps-3">
                            @foreach ($visit->leftItems as $item)
                                <li>{{ $item->quantity }} &times; {{ $item->colour }} {{ $item->item_name }}</li>
                            @endforeach
                        </ul>
                        <div class="small">Taken by <strong>{{ $visit->leftItems->first()->handler->name }}</strong></div>
                    </div>
                @endif
                @if ($visit->security)
                    <div class="alert alert-warning mt-2 mb-0">
                        <div class="small text-uppercase">Security officer</div>
                        <div class="fw-semibold">{{ $visit->security->force }} &middot; {{ $visit->security->officer_rank }}</div>
                        <div class="small">Protecting {{ $visit->security->vip_name }} ({{ $visit->security->vip_title }})</div>
                        @if ($visit->security->locker_number)
                            <div class="small">Locker {{ $visit->security->locker_number }} &middot; key card {{ $visit->visitor_card_number }}</div>
                        @endif
                        <div class="small mt-1">Arms taken in by <strong>{{ $visit->security->handler->name }}</strong></div>
                    </div>
                @endif
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
                
                @include('gate._exit-note', ['visit' => $visit])

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

                        @if ($visit->leftItems->isNotEmpty() || $visit->arms->isNotEmpty())
                            <div class="border border-warning rounded p-3 mb-3" id="return_block">
                                <h6 class="fw-bold mb-1">Hand back belongings and arms</h6>
                                <p class="small text-muted mb-3">
                                    Tick each item once it has been handed back to the visitor.
                                    If something cannot be returned, leave it unticked and write the reason.
                                </p>

                                @foreach ($visit->arms as $arm)
                                    <div class="return-row mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input return-tick" type="checkbox" name="returned_arms[]"
                                                   value="{{ $arm->id }}" id="arm_{{ $arm->id }}"
                                                   @checked(in_array($arm->id, array_map('intval', old('returned_arms', []))))>
                                            <label class="form-check-label fw-semibold" for="arm_{{ $arm->id }}">{{ $arm->summary() }}</label>
                                        </div>
                                        <input type="text" name="reasons_arms[{{ $arm->id }}]" maxlength="255"
                                               value="{{ old('reasons_arms.' . $arm->id) }}"
                                               class="form-control form-control-sm mt-1 return-reason"
                                               placeholder="Why was it not returned?">
                                    </div>
                                @endforeach

                                @foreach ($visit->leftItems as $item)
                                    <div class="return-row mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input return-tick" type="checkbox" name="returned_items[]"
                                                   value="{{ $item->id }}" id="item_{{ $item->id }}"
                                                   @checked(in_array($item->id, array_map('intval', old('returned_items', []))))>
                                            <label class="form-check-label" for="item_{{ $item->id }}">
                                                {{ $item->quantity }} &times; {{ $item->colour }} {{ $item->item_name }}
                                            </label>
                                        </div>
                                        <input type="text" name="reasons_items[{{ $item->id }}]" maxlength="255"
                                               value="{{ old('reasons_items.' . $item->id) }}"
                                               class="form-control form-control-sm mt-1 return-reason"
                                               placeholder="Why was it not returned?">
                                    </div>
                                @endforeach
                            </div>
                        @endif

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

                        <button type="submit" id="confirm_checkout" class="btn btn-danger btn-lg">Confirm Check-Out</button>
                    </form>
                @endif

            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    (function () {
        const block = document.getElementById('return_block');
        const button = document.getElementById('confirm_checkout');
        if (!block || !button) return;

        const rows = block.querySelectorAll('.return-row');

        // A row is settled when it is ticked, or a reason is written.
        function refresh() {
            let allSettled = true;

            rows.forEach(function (row) {
                const tick = row.querySelector('.return-tick');
                const reason = row.querySelector('.return-reason');
                reason.style.display = tick.checked ? 'none' : '';
                if (!tick.checked && reason.value.trim().length < 5) allSettled = false;
            });

            button.disabled = !allSettled;
        }

        block.addEventListener('change', refresh);
        block.addEventListener('input', refresh);
        refresh();
    })();
</script>
@endsection