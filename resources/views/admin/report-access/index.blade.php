@extends('layouts.app')

@section('title', 'User Access')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0">User access</h4>
                <small class="opacity-75">Choose who can see reports and use the staff laptop pages. The Administrator always has full access.</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Access</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php
                                    $isAdmin = $user->hasRole('Administrator');
                                    $mine = $user->getAllPermissions()->pluck('name')->filter(fn ($n) => isset($labels[$n]));
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $user->name }}</div>
                                        <div class="text-muted small">{{ $user->email }}</div>
                                    </td>
                                    <td>{{ $user->getRoleNames()->implode(', ') ?: '—' }}</td>
                                    <td>
                                        @if ($isAdmin)
                                            <span class="badge bg-primary">Full access</span>
                                        @elseif ($mine->isEmpty())
                                            <span class="text-muted small">No extra access</span>
                                        @else
                                            @foreach ($mine as $name)
                                                <span class="badge bg-light text-dark border">{{ $labels[$name] }}</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @unless ($isAdmin)
                                            <a href="{{ route('admin.report-access.edit', $user) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection