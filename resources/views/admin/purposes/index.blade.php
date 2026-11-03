@extends('layouts.app')

@section('title', 'Visit Purposes')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0">Purposes of Visit</h4>
                <small class="opacity-75">These appear in the Gate and booking forms</small>
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
                        <input type="text" id="new-name" name="name" class="form-control" maxlength="100"
                               placeholder="e.g. Training" required>
                        <button type="submit" class="btn btn-primary">Add</button>
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
                                        <form method="POST" action="{{ route('admin.purposes.update', $purpose) }}" class="d-flex gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" value="{{ $purpose->name }}" maxlength="100"
                                                   class="form-control form-control-sm" required>
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