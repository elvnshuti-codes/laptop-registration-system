@extends('layouts.app')

@section('content')

    <h1>User Management</h1>

    <a href="{{ route('users.create') }}" class="btn btn-primary mb-3">+ Add User</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role(s)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @forelse ($user->roles as $role)
                            <span class="badge bg-secondary">{{ $role->name }}</span>
                        @empty
                            <span class="text-muted">No role assigned</span>
                        @endforelse
                    </td>
                    <td>
    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    @if ($user->id !== auth()->id())
        <form action="{{ route('users.destroy', $user) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
    @endif
</td>
                </tr>
            @endforeach
        </tbody>
    </table>

@endsection