@extends('layouts.app')

@section('content')

    <h1>Check In a Laptop</h1>

    <form action="{{ route('registrations.store') }}" method="POST">
        @csrf

        <div class="mb-3">
    <label class="form-label">Employee Name</label>
    <input type="text" name="employee_name" class="form-control @error('employee_name') is-invalid @enderror" value="{{ old('employee_name') }}">
    @error('employee_name')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
            <label class="form-label">Employee ID Number</label>
            <input type="text" name="employee_id_number" class="form-control @error('employee_id_number') is-invalid @enderror" value="{{ old('employee_id_number') }}">
            @error('employee_id_number')
                <div class="text-danger">{{ $message }}</div>
            @enderror
     </div>
        
            <div class="mb-3">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control @error('department') is-invalid @enderror" value="{{ old('department') }}">
            @error('department')
                <div class="text-danger">{{ $message }}</div>
            @enderror
    </div>

            <div class="mb-3">
            <label class="form-label">Laptop Type</label>
            <input type="text" name="laptop_type" class="form-control @error('laptop_type') is-invalid @enderror" value="{{ old('laptop_type') }}">
            @error('laptop_type')
                <div class="text-danger">{{ $message }}</div>
            @enderror
    </div>

        <button type="submit" class="btn btn-primary">Check In</button>
    </form>

@endsection