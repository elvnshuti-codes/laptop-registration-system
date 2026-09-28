@extends('layouts.app')

@section('content')

    <h1>Change Your Password</h1>

    <p class="text-muted">You must set a new password before continuing.</p>

    <form action="{{ route('password.change.submit') }}" method="POST" style="max-width: 400px;">
        @csrf

        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
            @error('password')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="password_confirmation" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>

@endsection