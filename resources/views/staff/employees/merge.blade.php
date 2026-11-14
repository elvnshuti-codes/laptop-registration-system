@extends('layouts.app')

@section('title', 'Merge Record')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0">Merge a temporary record</h4>
                <small class="opacity-75">The import could not tell who this laptop belongs to.</small>
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

                <div class="rounded border bg-light p-3 mb-4">
                    <div class="fw-semibold">{{ $employee->name }}
                        <span class="font-monospace text-muted">{{ $employee->employee_id_number }}</span>
                    </div>
                    <div class="small text-muted mb-2">{{ $employee->department }}</div>
                    @foreach ($employee->devices->where('is_active', true) as $device)
                        <div class="small">
                            {{ $device->owner_type === 'organization' ? 'UNBS' : 'Personal' }} {{ $device->device_type }}
                            &middot; <span class="font-monospace">{{ $device->asset_tag ?: $device->serial_number }}</span>
                        </div>
                    @endforeach
                    @if ($employee->review_note)
                        <div class="small mt-2">{{ $employee->review_note }}</div>
                    @endif
                </div>

                <form method="POST" action="{{ route('staff.employees.merge', $employee) }}"
                      onsubmit="return confirm('Move the devices to this person and remove the temporary record?')">
                    @csrf

                    <div class="mb-3">
                        <label for="target" class="form-label fw-semibold">Whose laptop is it?</label>
                        <input type="text" id="target" name="target" list="targets" value="{{ old('target') }}"
                               class="form-control" placeholder="Type a name or an employee ID, then pick from the list" required autofocus autocomplete="off">
                        <datalist id="targets">
                            @foreach ($targets as $target)
                                <option value="{{ $target->employee_id_number }} · {{ $target->name }}">{{ $target->department }}</option>
                            @endforeach
                        </datalist>
                        <div class="form-text">
                            The devices move to that person and this temporary record is removed.
                            If the laptop really belongs to someone who is not on the employee list, use <strong>Edit</strong> instead and give them a proper ID.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Merge</button>
                        <a href="{{ route('staff.employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection