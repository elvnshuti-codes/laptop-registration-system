@extends('layouts.app')

@section('title', 'User Access')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0">{{ $user->name }}</h4>
                <small class="opacity-75">{{ $user->email }} &middot; {{ $user->getRoleNames()->implode(', ') ?: 'No role' }}</small>
            </div>
            <div class="card-body p-4">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($isAdmin)
                    <div class="alert alert-info mb-0">The Administrator always has full access.</div>
                @else
                    <form method="POST" action="{{ route('admin.report-access.update', $user) }}">
                        @csrf
                        @method('PUT')

                        @php
                            $groups = [
                                ['Reports this person can use', 'rep', $reports],
                                ['Areas this person can see', 'area', $areas],
                                ['Staff laptops', 'staff', $staff],
                            ];
                        @endphp

                        @foreach ($groups as [$title, $prefix, $items])
                            <h6 class="fw-semibold {{ $loop->first ? '' : 'mt-4' }}">{{ $title }}</h6>
                            @foreach ($items as $key => [$label, $hint])
                                @php $fromRole = in_array($key, $viaRole, true); @endphp
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $key }}"
                                           id="{{ $prefix }}{{ $loop->index }}"
                                           @checked($fromRole || in_array($key, old('permissions', $direct), true))
                                           @disabled($fromRole)>
                                    <label class="form-check-label" for="{{ $prefix }}{{ $loop->index }}">
                                        <span class="fw-semibold">{{ $label }}</span>
                                        @if ($fromRole)
                                            <span class="badge bg-secondary ms-1">from role</span>
                                        @endif
                                        <span class="d-block text-muted small">{{ $hint }}</span>
                                    </label>
                                </div>
                            @endforeach
                        @endforeach

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <a href="{{ route('admin.report-access.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection