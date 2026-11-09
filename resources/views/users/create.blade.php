@extends('layouts.app')

@section('content')

    <h1>Add User</h1>

    <form action="{{ route('users.store') }}" method="POST" style="max-width: 500px;">
        @csrf

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
            @error('name')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
            @error('email')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
            @error('password')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select @error('role') is-invalid @enderror">
                <option value="">-- Select a role --</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
            @error('role')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3" id="location_wrap">
            <label class="form-label">Location <span class="text-muted">(Entry Officers only)</span></label>
            <select name="location" class="form-select @error('location') is-invalid @enderror">
                <option value="">-- Select a location --</option>
                @foreach (\App\Models\User::LOCATIONS as $location)
                    <option value="{{ $location }}" {{ old('location') == $location ? 'selected' : '' }}>{{ $location }}</option>
                @endforeach
            </select>
            @error('location')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">Create User</button>
    </form>

@endsection 

@section('scripts')
<script>
    // The Location box only matters for Entry Officers.
    const roleSelect = document.querySelector('select[name="role"]');
    const locationWrap = document.getElementById('location_wrap');
    function toggleLocation() {
        locationWrap.style.display = roleSelect.value === 'Entry Officer' ? '' : 'none';
    }
    roleSelect.addEventListener('change', toggleLocation);
    toggleLocation();
</script>
@endsection