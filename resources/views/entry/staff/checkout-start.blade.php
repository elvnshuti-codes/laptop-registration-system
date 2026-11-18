@extends('layouts.app')

@section('title', 'Staff Check-Out')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-danger text-white py-3">
                <h4 class="mb-0">Staff check-out</h4>
                <small class="opacity-75">Scan the staff ticket, or search for the employee.</small>
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

                <form method="GET" action="{{ route('entry.staff-out.start') }}" class="mb-4">
                    <label for="ticket" class="form-label fw-semibold">Staff ticket</label>
                    <div class="input-group input-group-lg">
                        <input type="text" id="ticket" name="ticket" class="form-control font-monospace"
                               placeholder="Scan the barcode on the ticket (ST-...)" autocomplete="off" autofocus>
                        <button class="btn btn-danger" type="submit">Find</button>
                    </div>
                </form>

                <form method="GET" action="{{ route('entry.staff-out.start') }}">
                    <label for="q" class="form-label fw-semibold">Or search by name or staff ID</label>
                    <div class="input-group">
                        <input type="text" id="q" name="q" class="form-control" value="{{ $q }}" placeholder="e.g. Nshuti">
                        <button class="btn btn-outline-primary" type="submit">Search</button>
                    </div>
                </form>

                @if ($q !== '')
                    <div class="list-group mt-3">
                        @forelse ($results as $employee)
                            <a href="{{ route('entry.staff-out.show', $employee) }}" class="list-group-item list-group-item-action">
                                <span class="fw-semibold">{{ $employee->name }}</span>
                                <span class="text-muted small font-monospace">{{ $employee->employee_id_number }}</span>
                                <span class="text-muted small">&middot; {{ $employee->department }}</span>
                            </a>
                        @empty
                            <div class="list-group-item text-muted">No employee found.</div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Inside now ({{ $inside->count() }})</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Employee</th><th>Devices</th><th>Checked in</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($inside as $entry)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $entry->employee->name }}</div>
                                    <div class="small text-muted">{{ ucfirst($entry->location) }}</div>
                                </td>
                                <td class="small">
                                    @foreach ($entry->devices->whereNotNull('open_device_id') as $row)
                                        <div>{{ $row->device->label() }}</div>
                                    @endforeach
                                </td>
                                <td class="small">
                                    {{ $entry->checked_in_at->format('d M, H:i') }}
                                    @if ($entry->isStale()) <span class="badge bg-danger">Not checked out</span> @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('entry.staff-out.show', $entry->employee_id) }}" class="btn btn-sm btn-outline-danger">Check out</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted p-4">Nobody is inside.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection