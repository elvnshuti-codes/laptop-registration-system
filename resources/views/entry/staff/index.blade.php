@extends('layouts.app')

@section('title', 'Staff Devices')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0">Staff devices: check in</h4>
                <small class="opacity-75">Find the employee, scan their devices, then print the ticket.</small>
            </div>
            <div class="card-body p-4">
                <form method="GET" action="{{ route('entry.staff.index') }}" class="d-flex gap-2">
                    <input type="text" name="q" value="{{ $q }}" class="form-control form-control-lg"
                           placeholder="Type a name or employee ID" autofocus autocomplete="off">
                    <button type="submit" class="btn btn-primary btn-lg">Find</button>
                </form>

                @if ($q !== '')
                    @if ($results->isEmpty())
                        <div class="alert alert-warning mt-3 mb-0">No active employee matches &ldquo;{{ $q }}&rdquo;.</div>
                    @else
                        <div class="list-group mt-3">
                            @foreach ($results as $employee)
                                <a href="{{ route('entry.staff.show', $employee) }}"
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">{{ $employee->name }}
                                            <span class="font-monospace text-muted small">{{ $employee->employee_id_number }}</span>
                                        </div>
                                        <div class="small text-muted">{{ $employee->department }}</div>
                                    </div>
                                    <span class="badge {{ $employee->device_count ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $employee->device_count }} device(s)
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-light py-3 fw-semibold">Staff currently inside ({{ $inside->count() }})</div>
            @if ($inside->isEmpty())
                <div class="card-body text-muted">Nobody is checked in.</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Employee</th><th>Devices</th><th>Since</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($inside as $entry)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $entry->employee->name }}</div>
                                        <div class="small text-muted">{{ $entry->employee->department }}</div>
                                    </td>
                                    <td class="small">
                                        @foreach ($entry->devices as $row)
                                            <div>{{ $row->device->owner_type === 'organization' ? 'UNBS' : 'Personal' }} {{ $row->device->device_type }}
                                                <span class="font-monospace text-muted">{{ $row->device->sticker_code }}</span></div>
                                        @endforeach
                                    </td>
                                    <td class="small">{{ $entry->checked_in_at->format('d M H:i') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('entry.staff.ticket', $entry) }}" class="btn btn-sm btn-outline-dark">Ticket</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection