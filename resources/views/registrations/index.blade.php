@extends('layouts.app')

@section('content')

<h1>Laptop Registrations</h1>

<label class="form-label fw-bold">Search All Records (Database)</label>
<form action="{{ route('registrations.index') }}" method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search by employee name or ID..." value="{{ request('search') }}">
        <button type="submit" class="btn btn-outline-secondary">Search</button>
        <a href="{{ route('registrations.index') }}" class="btn btn-outline-danger">Clear</a>
    </div>
</form>
<p class="text-muted small mb-2">
    Filter records showning on this page.
</p>
<table class="table table-bordered mt-3" id="registrationsTable">
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Employee ID</th>
                <th>Department</th>
                <th>Laptop Type</th>
                <th>Checked In</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($registrations as $registration)
                <tr>
                    <td>{{ $registration->employee_name }}</td>
                    <td>{{ $registration->employee_id_number }}</td>
                    <td>{{ $registration->department }}</td>
                    <td>{{ $registration->laptop_type }}</td>
                    <td>{{ $registration->checked_in_at }}</td>
                    <td>
                       @if ($registration->checked_out_at)
                       <span class="text-secondary">Checked Out at {{ $registration->checked_out_at }}</span>
                       @else
                    <span class="text-success">Checked In</span>
                      @endif
                    </td>
            <td>
            <a href="{{ route('registrations.edit', $registration) }}" class="btn btn-sm btn-outline-primary">Edit</a>
            <form action="{{ route('registrations.destroy', $registration) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this registration?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
    </form>
</td> 
                </tr>
            @endforeach
        </tbody>
    </table>

@endsection

    @section('scripts')
    <script>
    $(document).ready(function () {
    $('#registrationsTable').DataTable();
        });

    </script>
@endsection