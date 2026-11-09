@extends('layouts.app')

@section('title', 'Sample Types')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Sample Types</h4>
                    <small class="opacity-75">These appear in the Gate form for Testing visitors</small>
                </div>
                <a href="{{ route('admin.purposes.index') }}" class="btn btn-sm btn-outline-light">&larr; Purposes</a>
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

                <form method="POST" action="{{ route('admin.sample-types.store') }}" class="mb-4">
                    @csrf
                    <label for="new-name" class="form-label">Add a sample type</label>
                    <div class="input-group">
                        <input type="text" id="new-name" name="name" class="form-control" maxlength="100"
                               placeholder="e.g. Electrical" required>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>

                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Sample type</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($types as $type)
                            <tr>
                                <td>
                                    <form method="POST" action="{{ route('admin.sample-types.update', $type) }}" class="d-flex gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $type->name }}" maxlength="100"
                                               class="form-control form-control-sm" required>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.sample-types.destroy', $type) }}" class="d-inline"
                                          onsubmit="return confirm('Delete the sample type &quot;{{ $type->name }}&quot;? Existing records keep their wording.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection