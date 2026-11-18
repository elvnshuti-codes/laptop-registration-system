@extends('layouts.app')

@section('title', 'Issued Devices')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0">Issued devices</h4>
                <small class="opacity-75">UNBS devices handed to an employee during the day, before any check-in.</small>
            </div>
            <div class="card-body p-4">
                <form method="GET" action="{{ route('issued.index') }}">
                    <label for="q" class="form-label fw-semibold">Find the device or the employee</label>
                    <div class="input-group">
                        <input type="text" id="q" name="q" value="{{ $q }}" class="form-control"
                               placeholder="Name, staff ID, asset tag, serial or sticker code">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>

                @if ($q !== '')
                    <div class="list-group mt-3">
                        @forelse ($results as $device)
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $device->label() }}</div>
                                    <div class="small text-muted">
                                        {{ $device->employee->name }} &middot; {{ $device->employee->department }}
                                        @if ($device->asset_tag) &middot; {{ $device->asset_tag }} @endif
                                    </div>
                                </div>
                                @if ($device->isIssuedOpen())
                                    <span class="badge bg-warning text-dark">Already issued {{ $device->issued_at->format('d M H:i') }}</span>
                                @else
                                    <form method="POST" action="{{ route('issued.issue', $device) }}"
                                          onsubmit="return confirm('Issue {{ addslashes($device->label()) }} to {{ addslashes($device->employee->name) }} now?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">Issue now</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div class="list-group-item text-muted">No UNBS device found.</div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-semibold">Waiting for the owner to collect ({{ $awaiting->count() }})</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Device</th><th>Owner</th><th>Issued</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($awaiting as $device)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $device->label() }}</div>
                                    @if ($device->asset_tag) <div class="small text-muted">{{ $device->asset_tag }}</div> @endif
                                </td>
                                <td>
                                    {{ $device->employee->name }}
                                    <div class="small text-muted">{{ $device->employee->email ?: 'No email on record' }}</div>
                                </td>
                                <td class="small">
                                    {{ $device->issued_at->format('d M Y, H:i') }}
                                    <div class="text-muted">by {{ $device->issuer?->name }}</div>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('issued.collect', $device) }}"
                                          onsubmit="return confirm('Has {{ addslashes($device->employee->name) }} collected this device? An email will be sent to them.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Collected</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted p-4">No device is waiting.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Collected, not yet checked out ({{ $collected->count() }})</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Device</th><th>Owner</th><th>Issued</th><th>Collected</th></tr></thead>
                    <tbody>
                        @forelse ($collected as $device)
                            <tr>
                                <td class="fw-semibold">{{ $device->label() }}</td>
                                <td>{{ $device->employee->name }}</td>
                                <td class="small">{{ $device->issued_at->format('d M Y, H:i') }}</td>
                                <td class="small">
                                    {{ $device->collected_at->format('d M Y, H:i') }}
                                    <div class="text-muted">{{ $device->collector?->name }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted p-4">None.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection