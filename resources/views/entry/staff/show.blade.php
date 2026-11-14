@extends('layouts.app')

@section('title', 'Staff Check-In')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0">{{ $employee->name }}</h4>
                <small class="opacity-75">
                    <span class="font-monospace">{{ $employee->employee_id_number }}</span>
                    &middot; {{ $employee->position }} &middot; {{ $employee->department }}
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

                @if ($open)
                    <div class="alert alert-warning">
                        Already checked in at {{ ucfirst($open->location) }} since {{ $open->checked_in_at->format('d M H:i') }}.
                        <a href="{{ route('entry.staff.ticket', $open) }}" class="alert-link">Open the ticket</a>
                    </div>
                @elseif ($devices->isEmpty())
                    <div class="alert alert-info mb-0">
                        This employee has no registered devices. The Registration Officer can add them under Staff Laptops &rarr; Employees &rarr; Devices.
                    </div>
                @else
                    <form method="POST" action="{{ route('entry.staff.store', $employee) }}" id="checkin-form">
                        @csrf

                        <p class="text-muted mb-2">Check this is the right person, then scan the sticker on each device they bring in.</p>

                        <div class="mb-3">
                            <input type="text" id="scan" class="form-control form-control-lg font-monospace"
                                   placeholder="Scan a sticker here" autocomplete="off" autofocus>
                            <div id="scan-msg" class="small mt-2"></div>
                        </div>

                        <div class="d-grid gap-2 mb-4">
                            @foreach ($devices as $device)
                                <div class="border rounded p-3 d-flex justify-content-between align-items-start gap-3 device-row"
                                     data-code="{{ $device->sticker_code }}">
                                    <div>
                                        <div class="fw-semibold">
                                            <span class="badge {{ $device->owner_type === 'organization' ? 'bg-primary' : 'bg-info text-dark' }}">
                                                {{ $device->owner_type === 'organization' ? 'UNBS' : 'Personal' }}
                                            </span>
                                            {{ $device->device_type }} {{ $device->make_model }}
                                        </div>
                                        <div class="small text-muted">
                                            @if ($device->asset_tag) Asset tag <span class="font-monospace">{{ $device->asset_tag }}</span> @endif
                                            @if ($device->asset_tag && $device->serial_number) &middot; @endif
                                            @if ($device->serial_number) Serial <span class="font-monospace">{{ $device->serial_number }}</span> @endif
                                        </div>
                                    </div>
                                    <span class="badge bg-secondary status">Waiting for scan</span>
                                </div>
                            @endforeach
                        </div>

                        <div id="codes"></div>

                        <div class="d-flex gap-2">
                            <button type="submit" id="checkin-button" class="btn btn-success btn-lg" disabled>Check in</button>
                            <a href="{{ route('entry.staff.index') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
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
        const button = document.getElementById('checkin-button');
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

        // Scans already made before the page reloaded with an error.
        @json(old('codes', [])).forEach(function (code) { accept(String(code).toUpperCase()); });

        // The scanner types the code and presses Enter: mark that device, never submit the form.
        scan.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();

            const code = scan.value.trim().toUpperCase();
            scan.value = '';
            if (!code) return;

            if (!rows[code]) {
                msg.className = 'small mt-2 text-danger fw-semibold';
                msg.textContent = code + ' is not on this person\'s list. Check the sticker, or that this is the right person.';
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