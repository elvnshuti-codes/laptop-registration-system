@extends('layouts.app')

@section('content')

    <h1>Laptop Registrations</h1>

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Employee ID</th>
                <th>Department</th>
                <th>Laptop Type</th>
                <th>Checked In</th>
                <th>Status</th>
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
                </tr>
            @endforeach
        </tbody>
    </table>

@endsection