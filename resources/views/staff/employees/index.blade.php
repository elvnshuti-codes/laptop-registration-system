@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Employees</h3>
        <div class="text-muted">The staff list that devices are registered against.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('staff.stickers.pending') }}" class="btn btn-outline-dark">Print unprinted stickers</a>
        <a href="{{ route('staff.employees.create') }}" class="btn btn-primary">+ Add employee</a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-3">
        <table id="employees" class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Devices</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($employees as $employee)
                    <tr>
                        <td class="font-monospace fw-semibold">{{ $employee->employee_id_number }}</td>
                        <td>{{ $employee->name }}</td>
                        <td>{{ $employee->position }}</td>
                        <td>{{ $employee->department }}</td>
                        <td data-order="{{ $employee->organization_count + $employee->personal_count }}">
                            @if ($employee->organization_count + $employee->personal_count === 0)
                                <span class="text-muted small">None</span>
                            @else
                                @if ($employee->organization_count)
                                    <span class="badge bg-primary">Organization {{ $employee->organization_count }}</span>
                                @endif
                                @if ($employee->personal_count)
                                    <span class="badge bg-info text-dark">Personal {{ $employee->personal_count }}</span>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if ($employee->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('staff.devices.index', $employee) }}" class="btn btn-sm btn-outline-dark">Devices</a>
                                <a href="{{ route('staff.employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('staff.employees.toggle', $employee) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $employee->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                        {{ $employee->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        $('#employees').DataTable({
            order: [[1, 'asc']],
            pageLength: 25,
            columnDefs: [{ orderable: false, targets: -1 }],
        });
    });
</script>
@endsection