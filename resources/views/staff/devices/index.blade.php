@extends('layouts.app')

@section('title', 'Staff Devices')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h3 class="mb-0">{{ $employee->name }}</h3>
        <div class="text-muted">
            <span class="font-monospace">{{ $employee->employee_id_number }}</span> &middot; {{ $employee->position }} &middot; {{ $employee->department }}
            @unless ($employee->is_active)
                <span class="badge bg-secondary ms-1">Inactive</span>
            @endunless
        </div>
    </div>
    <a href="{{ route('staff.employees.index') }}" class="btn btn-outline-secondary">&larr; Employees</a>
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

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3"><h5 class="mb-0">Registered devices</h5></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Barcode reads</th>
                            <th>Device</th>
                            <th>Owner</th>
                            <th>Serial number</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($devices as $device)
                            <tr class="{{ $device->is_active ? '' : 'text-muted' }}">
                                <td>
                                    <div class="font-monospace fw-semibold">{{ $device->sticker_code }}</div>
                                    <div class="small">
                                        @if ($device->sticker_printed_at)
                                            Printed {{ $device->sticker_printed_at->format('d M Y') }}
                                        @else
                                            Not printed yet
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $device->make_model ?: $device->device_type }}</div>
                                    @if ($device->make_model)
                                        <div class="small">{{ $device->device_type }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $device->owner_type === 'organization' ? 'bg-primary' : 'bg-info text-dark' }}">
                                        {{ $ownerTypes[$device->owner_type] }}
                                    </span>
                                    @unless ($device->is_active)
                                        <span class="badge bg-secondary">Retired</span>
                                    @endunless
                                </td>
                                <td class="font-monospace small">{{ $device->serial_number }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('staff.devices.toggle', $device) }}"
                                          onsubmit="return confirm('{{ $device->is_active ? 'Retire' : 'Restore' }} {{ $device->sticker_code }}?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $device->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $device->is_active ? 'Retire' : 'Restore' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No devices registered yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3"><h5 class="mb-0">Register a device</h5></div>
            <div class="card-body">
                @if (! $employee->is_active)
                    <div class="alert alert-warning mb-0">Activate this employee before registering a device.</div>
                @elseif ($organizationFull && $personalFull)
                    <div class="alert alert-info mb-0">
                        This employee has the maximum devices: 1 UNBS device and 2 personal devices. Retire one to register another.
                    </div>
                @else
                    <form method="POST" action="{{ route('staff.devices.store', $employee) }}">
                        @csrf

                        <div class="mb-3">
                            <label for="owner_type" class="form-label fw-semibold">Owned by</label>
                            <select id="owner_type" name="owner_type" class="form-select" required>
                                <option value="">Select...</option>
                                @foreach ($ownerTypes as $value => $label)
                                    @php $full = $value === 'organization' ? $organizationFull : $personalFull; @endphp
                                    <option value="{{ $value }}" @selected(old('owner_type') === $value) @disabled($full)>
                                        {{ $value === 'organization' ? 'UNBS (organization)' : 'Personal' }}{{ $full ? ' (limit reached)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="device_type" class="form-label fw-semibold">Device type</label>
                            <select id="device_type" name="device_type" class="form-select" required>
                                @foreach ($deviceTypes as $type => $prefix)
                                    <option value="{{ $type }}" @selected(old('device_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="make_model" class="form-label fw-semibold">Name / model <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" id="make_model" name="make_model" maxlength="100" value="{{ old('make_model') }}"
                                   class="form-control" placeholder="e.g. Lenovo ThinkPad">
                        </div>

                        <div class="mb-3">
                            <label for="serial_number" class="form-label fw-semibold">Serial number</label>
                            <input type="text" id="serial_number" name="serial_number" maxlength="60" value="{{ old('serial_number') }}"
                                   class="form-control font-monospace" placeholder="e.g. PF-646CSR" required>
                        </div>

                        <div class="mb-3" id="asset-tag-box">
                            <label for="asset_tag" class="form-label fw-semibold">UNBS asset tag</label>
                            <input type="text" id="asset_tag" name="asset_tag" maxlength="60" value="{{ old('asset_tag') }}"
                                   class="form-control font-monospace" placeholder="e.g. 154-IT-LP-2026">
                        </div>

                        <div class="rounded border bg-light p-2 mb-3">
                            <div class="small text-muted">The barcode will read</div>
                            <div id="code-preview" class="font-monospace fw-semibold">&mdash;</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Register device</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var prefixes = @json($deviceTypes);

        // Same cleaning as the server: uppercase, no spaces.
        function clean(value) {
            return (value || '').replace(/\s+/g, '').toUpperCase();
        }

        function refresh() {
            var owner = $('#owner_type').val();
            var isPersonal = owner === 'personal';

            // The asset tag only applies to UNBS (organization) devices.
            $('#asset-tag-box').toggle(! isPersonal);
            $('#asset_tag').prop('required', owner === 'organization');

            var code = '';
            if (owner === 'organization') {
                code = clean($('#asset_tag').val());
            } else if (isPersonal && $('#serial_number').val()) {
                code = prefixes[$('#device_type').val()] + '-' + clean($('#serial_number').val());
            }

            $('#code-preview').text(code || '\u2014');
        }

        $('#owner_type, #device_type, #serial_number, #asset_tag').on('input change', refresh);
        refresh();
    });
</script>
@endsection