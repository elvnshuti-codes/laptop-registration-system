@extends('layouts.app')

@section('title', 'Visit Purposes')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Purposes of Visit</h4>
                    <small class="opacity-75">These appear in the Gate and booking forms</small>
                </div>
                <a href="{{ route('admin.sample-types.index') }}" class="btn btn-sm btn-outline-light">Sample types &rarr;</a>
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

                <form method="POST" action="{{ route('admin.purposes.store') }}" class="mb-4">
                    @csrf
                    <label for="new-name" class="form-label">Add a purpose</label>
                    <div class="input-group">
                        <input type="text" id="new-name" name="name" class="form-control"maxlength="100"
                               placeholder="e.g. Training" required>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_testing"value="1" id="new-testing">
                        <label class="form-check-label" for="new-testing">Visitors with this purpose go to Sample Reception</label>
                    </div>
                    <div class="mt-2">
                        <span class="small text-muted me-2">Offered at:</span>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="at_main" value="1" id="new-main" checked>
                            <label class="form-check-label" for="new-main">Main site</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="at_rig" value="1" id="new-rig">
                            <label class="form-check-label" for="new-rig">Rig</label>
                        </div>
                    </div>
                </form>

                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Purpose</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purposes as $purpose)
                            <tr>
                                @if ($purpose->is_system)
                                    <td>{{ $purpose->name }} <span class="badge bg-secondary">Built in</span></td>
                                    <td class="text-end text-muted small">Cannot be changed</td>
                                @else
                                    <td>
                                        <form method="POST" action="{{ route('admin.purposes.update', $purpose) }}" class="d-flex gap-2 align-items-center">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" value="{{ $purpose->name }}" maxlength="100"
                                                   class="form-control form-control-sm" required>
                                            <div class="form-check text-nowrap mb-0">
                                                <input class="form-check-input" type="checkbox" name="is_testing" value="1"
                                                       id="testing-{{ $purpose->id }}" @checked($purpose->is_testing)>
                                                <label class="form-check-label small" for="testing-{{ $purpose->id }}">Sample Reception</label>
                                            </div>
                                            <div class="form-check text-nowrap mb-0">
                                                <input class="form-check-input" type="checkbox" name="at_main" value="1"
                                                       id="main-{{ $purpose->id }}" @checked($purpose->at_main)>
                                                <label class="form-check-label small" for="main-{{ $purpose->id }}">Main</label>
                                            </div>
                                            <div class="form-check text-nowrap mb-0">
                                                <input class="form-check-input" type="checkbox" name="at_rig" value="1"
                                                       id="rig-{{ $purpose->id }}" @checked($purpose->at_rig)>
                                                <label class="form-check-label small" for="rig-{{ $purpose->id }}">Rig</label>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.purposes.destroy', $purpose) }}" class="d-inline"
                                              onsubmit="return confirm('Delete the purpose &quot;{{ $purpose->name }}&quot;? Existing records keep their wording.');">
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
            </div>
        </div>
    </div>
</div>
@endsection