@php $e = $employee ?? null; @endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-3">
    <label for="employee_id_number" class="form-label fw-semibold">Employee ID</label>
    <input type="text" id="employee_id_number" name="employee_id_number" maxlength="30"
           value="{{ old('employee_id_number', $e?->employee_id_number) }}"
           class="form-control font-monospace" placeholder="e.g. UNBSGT013" required autofocus>
</div>

<div class="mb-3">
    <label for="name" class="form-label fw-semibold">Full name</label>
    <input type="text" id="name" name="name" maxlength="255" value="{{ old('name', $e?->name) }}" class="form-control" required>
</div>

<div class="mb-3">
    <label for="position" class="form-label fw-semibold">Position</label>
    <input type="text" id="position" name="position" maxlength="120" list="position-list"
           value="{{ old('position', $e?->position) }}" class="form-control" required>
    <datalist id="position-list">
        @foreach ($positions as $position)
            <option value="{{ $position }}"></option>
        @endforeach
    </datalist>
</div>

<div class="mb-3">
    <label for="department" class="form-label fw-semibold">Department</label>
    <input type="text" id="department" name="department" maxlength="255" list="department-list"
           value="{{ old('department', $e?->department) }}" class="form-control" required>
    <datalist id="department-list">
        @foreach ($departments as $department)
            <option value="{{ $department }}"></option>
        @endforeach
    </datalist>
</div>

<div class="mb-3">
    <label for="email" class="form-label fw-semibold">Email (UNBS)</label>
    <input type="email" id="email" name="email" value="{{ old('email', $employee->email ?? '') }}" class="form-control" autocomplete="off">
</div>