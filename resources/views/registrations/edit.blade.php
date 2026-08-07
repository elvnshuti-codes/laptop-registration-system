@extends('layouts.app')

@section('content')

    <h1>Edit / Check Out Laptop</h1>

    <form action="{{ route('registrations.update', $registration) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Employee Name</label>
            <input type="text" name="employee_name" class="form-control @error('employee_name') is-invalid @enderror" value="{{ old('employee_name', $registration->employee_name) }}">
            @error('employee_name')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Employee ID Number</label>
            <input type="text" name="employee_id_number" class="form-control @error('employee_id_number') is-invalid @enderror" value="{{ old('employee_id_number', $registration->employee_id_number) }}">
            @error('employee_id_number')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control @error('department') is-invalid @enderror" value="{{ old('department', $registration->department) }}">
            @error('department')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Laptop Type</label>
            <input type="text" name="laptop_type" class="form-control @error('laptop_type') is-invalid @enderror" value="{{ old('laptop_type', $registration->laptop_type) }}">
            @error('laptop_type')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        @if (!$registration->checked_out_at)
            <div class="form-check mb-3">
                <input type="checkbox" name="check_out" value="1" class="form-check-input" id="check_out">
                <label class="form-check-label" for="check_out">Check out this laptop now</label>
            </div>
        @elsev
            <p class="text-secondary">Already checked out at {{ $registration->checked_out_at }}</p>
        @endif

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form> 

@endsection
 