@extends('layouts.app')

@section('title', 'Import Employees')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0">Import employees and UNBS laptops</h4>
                <small class="opacity-75">Upload the lists, check the preview, then apply. Nothing is saved until you press Apply.</small>
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

                <form method="POST" action="{{ route('staff.import.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label for="employees_file" class="form-label fw-semibold">Employee list</label>
                        <input type="file" id="employees_file" name="employees_file" class="form-control" accept=".xlsx,.xls,.csv">
                        <div class="form-text">
                            Columns: <strong>Employee ID, First name, Other name, Last Name, Position, Department</strong>.
                            Existing employee IDs are updated, new ones are added.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="laptops_file" class="form-label fw-semibold">UNBS laptop list</label>
                        <input type="file" id="laptops_file" name="laptops_file" class="form-control" accept=".xlsx,.xls,.csv">
                        <div class="form-text">
                            Columns: <strong>Asset tag, Employee name, Department</strong>.
                            Each laptop is linked to the employee with the same name.
                        </div>
                    </div>

                    <div class="rounded border bg-light p-3 mb-4 small">
                        <div class="fw-semibold mb-1">How laptops are matched to employees</div>
                        <ul class="mb-0 ps-3">
                            <li>Two or more names in common, in any order, count as the same name. The employee is saved with all their names.</li>
                            <li>Same name and same department: the laptop is linked.</li>
                            <li>Same name, a department missing in one list: linked, and marked <em>Needs review</em>.</li>
                            <li>Same name, different departments, or several possible people, or nobody found: the laptop is saved on a separate record with a temporary ID (<span class="font-monospace">TEMP-0001</span>), marked <em>Needs review</em>, for you to sort out.</li>
                        </ul>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Preview import</button>
                        <a href="{{ route('staff.employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection