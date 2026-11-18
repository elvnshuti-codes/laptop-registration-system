{{-- A personal gadget that is not in the system: register it with a pre-printed sticker --}}
<details class="border rounded p-3 mt-4" @if ($errors->has('sticker_code') || $errors->has('make_model') || $errors->has('colour') || $errors->has('device_type')) open @endif>
    <summary class="fw-semibold">Add a gadget that is not in the system</summary>
    <form method="POST" action="{{ route('entry.staff-gadgets.store', $employee) }}" class="row g-2 mt-2">
        @csrf
        <div class="col-md-4">
            <label class="form-label small mb-1">Type</label>
            <select name="device_type" class="form-select" required>
                @foreach (\App\Http\Controllers\StaffGadgetController::TYPES as $type)
                    <option value="{{ $type }}" @selected(old('device_type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label small mb-1">Name or make</label>
            <input type="text" name="make_model" value="{{ old('make_model') }}" class="form-control" placeholder="e.g. HP Pavilion" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small mb-1">Colour</label>
            <input type="text" name="colour" value="{{ old('colour') }}" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small mb-1">Seal number <span class="text-muted">(if sealed)</span></label>
            <input type="text" name="seal_number" value="{{ old('seal_number') }}" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label small mb-1">Scan the new sticker, then stick it on the gadget</label>
            <input type="text" name="sticker_code" value="{{ old('sticker_code') }}" class="form-control font-monospace"
                   placeholder="UNBS-WG-..." autocomplete="off" required>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-outline-dark">Register gadget</button>
            <span class="small text-muted ms-2">Stays registered to {{ $employee->name }}. Next time, just scan it.</span>
        </div>
    </form>
</details>