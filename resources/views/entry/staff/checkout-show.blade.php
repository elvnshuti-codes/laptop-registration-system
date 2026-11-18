@extends('layouts.app')

@section('title', 'Staff Check-Out')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-danger text-white py-3">
                <h4 class="mb-0">Check out: {{ $employee->name }}</h4>
                <small class="opacity-75">
                    <span class="font-monospace">{{ $employee->employee_id_number }}</span> &middot; {{ $employee->department }}
                </small>
            </div>
            <div class="card-body p-4">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($entry && $entry->isStale())
                    <div class="alert alert-warning">
                        This entry is from <strong>{{ $entry->checked_in_at->format('d M Y, H:i') }}</strong> and was never checked out.
                    </div>
                @endif

                @if ($wrongPlace)
                    <div class="alert alert-danger">
                        <strong>These devices were checked in at {{ ucfirst($entry->location) }}.</strong>
                        They must be checked out at {{ ucfirst($entry->location) }}. Admin can override this later if needed.
                    </div>
                @endif

                @if ($onEntry->isEmpty() && $issued->isEmpty())
                    <div class="alert alert-info mb-0">
                        Nothing to check out here for {{ $employee->name }}: no open entry at this checkpoint and no device issued today.
                    </div>
                @else
                    <form method="POST" action="{{ route('entry.staff-out.store', $employee) }}" id="checkout-form">
                        @csrf

                        <p class="text-muted mb-2">Scan the sticker on each device that is leaving.</p>

                        <div class="mb-3">
                            <input type="text" id="scan" class="form-control form-control-lg font-monospace"
                                   placeholder="Scan a sticker here" autocomplete="off" autofocus>
                            <div id="scan-msg" class="small mt-2"></div>
                        </div>

                        <div class="d-grid gap-2 mb-4">
                            @foreach ($onEntry as $row)
                                <div class="border rounded p-3 d-flex justify-content-between align-items-start gap-3 device-row"
                                     data-code="{{ $row->device->sticker_code }}">
                                    <div>
                                        <div class="fw-semibold">
                                            <span class="badge {{ $row->device->owner_type === 'organization' ? 'bg-primary' : 'bg-info text-dark' }}">
                                                {{ $row->device->owner_type === 'organization' ? 'UNBS' : 'Personal' }}
                                            </span>
                                            {{ $row->device->device_type }} {{ $row->device->make_model }}
                                        </div>
                                        <div class="small text-muted font-monospace">{{ $row->device->sticker_code }}</div>
                                        @if ($row->adminRequest)
                                            <div class="small text-success">Admitted with Admin approval ({{ $row->adminRequest->reference() }})</div>
                                        @endif
                                    </div>
                                    <span class="badge bg-secondary status">Waiting for scan</span>
                                </div>
                            @endforeach

                            @foreach ($issued as $device)
                                <div class="border border-warning rounded p-3 d-flex justify-content-between align-items-start gap-3 device-row"
                                     data-code="{{ $device->sticker_code }}">
                                    <div>
                                        <div class="fw-semibold">
                                            <span class="badge bg-primary">UNBS</span>
                                            {{ $device->device_type }} {{ $device->make_model }}
                                            <span class="badge bg-warning text-dark">Issued, no check-in</span>
                                        </div>
                                        <div class="small text-muted font-monospace">{{ $device->sticker_code }}</div>
                                        <div class="small">
                                            Issued <strong>{{ $device->issued_at->format('d M Y, H:i') }}</strong>
                                            @if ($device->collected_at) &middot; collected {{ $device->collected_at->format('d M Y, H:i') }} @endif
                                        </div>
                                    </div>
                                    <span class="badge bg-secondary status">Waiting for scan</span>
                                </div>
                            @endforeach
                        </div>

                        <div id="codes"></div>

                        <div class="d-flex gap-2">
                            <button type="submit" id="checkout-button" class="btn btn-danger btn-lg" disabled>Check out</button>
                            <a href="{{ route('entry.staff-out.start') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
                        </div>
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
        const scan = document.getElementById('scan');
        if (!scan) return;

        const msg = document.getElementById('scan-msg');
        const codes = document.getElementById('codes');
        const button = document.getElementById('checkout-button');
        const rows = {};
        const scanned = new Set();

        document.querySelectorAll('.device-row').forEach(function (row) {
            rows[row.dataset.code.toUpperCase()] = row;
        });

        function accept(code) {
            const row = rows[code];
            if (!row || scanned.has(code)) return;

            scanned.add(code);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'codes[]';
            input.value = code;
            codes.appendChild(input);

            row.classList.add('border-success', 'bg-success-subtle');
            const status = row.querySelector('.status');
            status.className = 'badge bg-success status';
            status.textContent = 'Scanned';

            button.disabled = false;
        }

        @json(old('codes', [])).forEach(function (code) { accept(String(code).toUpperCase()); });

        scan.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();

            const code = scan.value.trim().toUpperCase();
            scan.value = '';
            if (!code) return;

            if (!rows[code]) {
                msg.className = 'small mt-2 text-danger fw-semibold';
                msg.textContent = code + ' is not on this person\'s open entry here, and it is not a device issued to them.';
                return;
            }

            if (scanned.has(code)) {
                msg.className = 'small mt-2 text-warning fw-semibold';
                msg.textContent = code + ' was already scanned.';
                return;
            }

            accept(code);
            msg.className = 'small mt-2 text-success fw-semibold';
            msg.textContent = code + ' scanned.';
        });
    })();
</script>
@endsection