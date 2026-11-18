@extends('layouts.app')

@section('title', 'Staff Check-In')

@section('content')
@php
    $blockedDevices = $devices->filter(fn ($d) => in_array($d->id, $issuedOpenIds, true));
    $hasIssued = $blockedDevices->isNotEmpty();
@endphp

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

                @if ($approvedToday->isNotEmpty())
                    <div class="alert alert-success">
                        <strong>Admin has approved:</strong>
                        @foreach ($approvedToday as $approval)
                            <div>
                                {{ $approval->reference() }}
                                &middot; {{ $approval->decider?->name }}, {{ $approval->decided_at->format('H:i') }}
                                @if ($approval->decision_note) &mdash; {{ $approval->decision_note }} @endif
                            </div>
                        @endforeach
                        <div class="small mt-1">Scan the device(s) below to check them in. They will show "Admitted with Admin approval".</div>
                    </div>
                @endif

                @if ($open && ! $stale)
                    {{-- Inside today --}}
                    <div class="alert alert-warning">
                        Already checked in at {{ ucfirst($open->location) }} since {{ $open->checked_in_at->format('d M H:i') }}.
                        <a href="{{ route('entry.staff.ticket', $open) }}" class="alert-link">Open the ticket</a>
                        &middot;
                        <a href="{{ route('entry.staff-out.show', $employee) }}" class="alert-link">Check out</a>
                    </div>
                @elseif ($stale)
                    {{-- An entry from an earlier day was never checked out: refused --}}
                    <div class="border border-danger border-2 rounded p-3 mb-3">
                        <h5 class="text-danger fw-bold mb-2">Check-in refused: not checked out</h5>
                        <p class="mb-2">
                            This person was checked in at <strong>{{ ucfirst($open->location) }}</strong> on
                            <strong>{{ $open->checked_in_at->format('d M Y, H:i') }}</strong>
                            by {{ $open->checkedInBy?->name }}, and was never checked out.
                        </p>

                        @if ($staleDevices->isNotEmpty())
                            <div class="small text-uppercase text-muted">UNBS devices still on that entry</div>
                            <ul>
                                @foreach ($staleDevices as $row)
                                    <li>{{ $row->device->label() }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="alert alert-danger py-2">
                            <strong>Keep the UNBS device. Do not hand it back to the owner.</strong>
                        </div>

                        @if ($staleRequest && $staleRequest->status === 'pending')
                            <div class="alert alert-warning mb-0">
                                Request <strong>{{ $staleRequest->reference() }}</strong> was sent to Admin at
                                {{ $staleRequest->created_at->format('d M H:i') }}. Waiting for an answer.
                            </div>
                        @elseif ($staleRequest && $staleRequest->status === 'declined')
                            <div class="alert alert-secondary mb-0">
                                <strong>{{ $staleRequest->reference() }} was declined</strong>
                                by {{ $staleRequest->decider?->name }}.
                                @if ($staleRequest->decision_note) &ldquo;{{ $staleRequest->decision_note }}&rdquo; @endif
                                Keep the device until Admin tells you what to do.
                            </div>
                        @else
                            <form method="POST" action="{{ route('entry.staff-requests.store', $employee) }}">
                                @csrf
                                <input type="hidden" name="type" value="stale_entry">
                                <input type="hidden" name="staff_entry_id" value="{{ $open->id }}">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="kept" id="kept_stale" value="1" required>
                                    <label class="form-check-label fw-semibold" for="kept_stale">I am keeping this device</label>
                                </div>
                                <button type="submit" class="btn btn-danger">Send request to Admin</button>
                            </form>
                        @endif
                    </div>
                @elseif ($devices->isEmpty())
                    <div class="alert alert-info mb-0">
                        This employee has no registered devices. The Registration Officer can add them under Staff Laptops &rarr; Employees &rarr; Devices.
                    </div>

                    @include('entry.staff.gadget-box')
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
                                @php
                                    $isBlocked = in_array($device->id, $issuedOpenIds, true);
                                    $req = $deviceRequests->get($device->id);
                                @endphp
                                <div class="border rounded p-3 d-flex justify-content-between align-items-start gap-3 device-row"
                                     data-code="{{ $device->sticker_code }}"
                                     @if ($isBlocked) data-blocked="Issued {{ $device->issued_at->format('d M Y, H:i') }} and never checked out. Keep the device and send a request to Admin below." @endif>
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
                                        @if ($isBlocked)
                                            <div class="small text-danger fw-semibold mt-1">
                                                Issued {{ $device->issued_at->format('d M Y, H:i') }}, never checked out
                                            </div>
                                        @endif
                                        @if ($req && $req->status === 'approved')
                                            <div class="small text-success fw-semibold mt-1">Approved by Admin: can be checked in</div>
                                        @endif
                                    </div>
                                    <span class="badge {{ $isBlocked ? 'bg-danger' : 'bg-secondary' }} status">
                                        {{ $isBlocked ? 'Refused' : 'Waiting for scan' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div id="codes"></div>

                        <div class="d-flex gap-2">
                            <button type="submit" id="checkin-button" class="btn btn-success btn-lg" disabled>Check in</button>
                            <a href="{{ route('entry.staff.index') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
                            @if ($hasIssued)
                                <a href="{{ route('entry.staff-out.show', $employee) }}" class="btn btn-outline-danger btn-lg">Check out</a>
                            @endif
                        </div>
                    </form>

                    @include('entry.staff.gadget-box')

                    {{-- Issued devices that never checked out: keep them, ask Admin --}}
                    @if ($hasIssued)
                        <div class="border border-danger rounded p-3 mt-4">
                            <h6 class="text-danger fw-bold">Issued devices that were never checked out</h6>
                            <div class="alert alert-danger py-2 small">
                                <strong>Keep these devices. Do not hand them back to the owner.</strong>
                                Admin must approve before they can be checked in.
                            </div>

                            @foreach ($blockedDevices as $device)
                                @php $req = $deviceRequests->get($device->id); @endphp
                                <div class="border-top pt-3 mt-3">
                                    <div class="fw-semibold">{{ $device->label() }}</div>
                                    <div class="small text-muted mb-2">
                                        Issued {{ $device->issued_at->format('d M Y, H:i') }}
                                        @if ($device->collected_at) &middot; collected {{ $device->collected_at->format('d M Y, H:i') }} @endif
                                    </div>

                                    @if ($req && $req->status === 'pending')
                                        <div class="alert alert-warning mb-0 py-2">
                                            Request <strong>{{ $req->reference() }}</strong> sent {{ $req->created_at->format('d M H:i') }}. Waiting for Admin.
                                        </div>
                                    @elseif ($req && $req->status === 'declined')
                                        <div class="alert alert-secondary mb-0 py-2">
                                            <strong>{{ $req->reference() }} declined</strong> by {{ $req->decider?->name }}.
                                            @if ($req->decision_note) &ldquo;{{ $req->decision_note }}&rdquo; @endif
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('entry.staff-requests.store', $employee) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="issued_device">
                                            <input type="hidden" name="staff_device_id" value="{{ $device->id }}">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="kept" id="kept_{{ $device->id }}" value="1" required>
                                                <label class="form-check-label" for="kept_{{ $device->id }}">I am keeping this device</label>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-danger">Send request to Admin</button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
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
            if (!row || scanned.has(code) || row.dataset.blocked) return;

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

            if (rows[code].dataset.blocked) {
                msg.className = 'small mt-2 text-danger fw-bold';
                msg.textContent = 'REFUSED: ' + code + '. ' + rows[code].dataset.blocked;
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