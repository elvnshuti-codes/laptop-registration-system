@extends('layouts.app')

@section('title', 'Unserved Reasons')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0">Reasons for leaving unserved</h4>
                <small class="opacity-75">Entry officers choose from this list when a visitor must leave without being served</small>
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

                <form method="POST" action="{{ route('admin.reasons.store') }}" class="mb-4">
                    @csrf
                    <label for="name" class="form-label fw-semibold">Add a reason</label>
                    <div class="input-group">
                        <input type="text" id="name" name="name" maxlength="100" class="form-control"
                               placeholder="e.g. Visitor changed their mind" value="{{ old('name') }}">
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="requires_note" value="1" id="new_requires_note">
                        <label class="form-check-label" for="new_requires_note">Entry officer must add a note for this reason</label>
                    </div>
                </form>

                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Reason</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reasons as $reason)
                            <tr>
                                @if ($reason->is_system)
                                    <td>
                                        {{ $reason->name }}
                                        <span class="badge bg-secondary">Built in</span>
                                        @if ($reason->requires_note)
                                            <span class="badge bg-light text-dark border">Note required</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-muted small">Cannot be changed</td>
                                @else
                                    <td>
                                        <form method="POST" action="{{ route('admin.reasons.update', $reason) }}"
                                              class="d-flex align-items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" maxlength="100" value="{{ $reason->name }}"
                                                   class="form-control form-control-sm">
                                            <div class="form-check text-nowrap mb-0">
                                                <input class="form-check-input" type="checkbox" name="requires_note" value="1"
                                                       id="rn{{ $reason->id }}" @checked($reason->requires_note)>
                                                <label class="form-check-label small" for="rn{{ $reason->id }}">Note required</label>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.reasons.destroy', $reason) }}"
                                              onsubmit="return confirm('Delete this reason? Past records keep their wording.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="text-muted small mb-0">Renaming or deleting a reason never changes past records: they keep the wording they had when recorded.</p>
            </div>
        </div>
    </div>
</div>
@endsection
