@extends('layouts.app')

@section('title', 'Pre-book a Visitor')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0">Pre-book a Visitor</h4>
                <small class="opacity-75">The system generates a code that is valid only on the expected date</small>
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

                <form method="POST" action="{{ route('reception.bookings.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="name" class="form-label">Visitor's full name</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control" required autofocus>
                        </div>
                        <div class="col-md-5">
                            <label for="visitor_mobile_number" class="form-label">Mobile number <span class="text-muted">(optional)</span></label>
                            <input type="text" id="visitor_mobile_number" name="visitor_mobile_number"
                                   value="{{ old('visitor_mobile_number') }}" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label for="purpose" class="form-label">Purpose of visit</label>
                            <select id="purpose" name="purpose" class="form-select" required>
                                <option value="">Select...</option>
                                @foreach ($purposes as $p)
                                    <option value="{{ $p }}" @selected(old('purpose') === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6" id="purpose-other-box" style="display:none;">
                            <label for="purpose_other" class="form-label">Please specify</label>
                            <input type="text" id="purpose_other" name="purpose_other" value="{{ old('purpose_other') }}" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label for="meeting_with" class="form-label">Who they are meeting</label>
                            <input type="text" id="meeting_with" name="meeting_with" value="{{ old('meeting_with') }}" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label for="expected_date" class="form-label">Expected date</label>
                            <input type="date" id="expected_date" name="expected_date"
                                   value="{{ old('expected_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}"
                                   class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label for="expected_time" class="form-label">Time <span class="text-muted">(optional)</span></label>
                            <input type="time" id="expected_time" name="expected_time" value="{{ old('expected_time') }}" class="form-control">
                        </div>

                        <div class="col-md-5">
                            <label for="id_document_type" class="form-label">ID document the visitor will carry</label>
                            <select id="id_document_type" name="id_document_type" class="form-select" required>
                                <option value="">Select...</option>
                                @foreach (['National ID', 'Passport', 'Driving Permit', 'Other'] as $d)
                                    <option value="{{ $d }}" @selected(old('id_document_type') === $d)>{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7" id="id-number-box" style="display:none;">
                            <label for="id_document_number" class="form-label" id="id-number-label">Document number</label>
                            <input type="text" id="id_document_number" name="id_document_number"
                                   value="{{ old('id_document_number') }}" class="form-control">
                            <div class="form-text">The visitor must carry this document on arrival.</div>
                        </div>
                        <div class="col-12" id="id-other-box" style="display:none;">
                            <label for="id_document_other" class="form-label">Name of the other document</label>
                            <input type="text" id="id_document_other" name="id_document_other" value="{{ old('id_document_other') }}" class="form-control">
                        </div>

                        <div class="col-12">
                            <label for="device_note" class="form-label">Expected devices <span class="text-muted">(optional note)</span></label>
                            <textarea id="device_note" name="device_note" rows="2" class="form-control"
                                      placeholder="e.g. one laptop and a projector">{{ old('device_note') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create booking</button>
                        <a href="{{ route('reception.bookings.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    var labels = {
        'National ID': 'National ID number (NIN)',
        'Passport': 'Passport number',
        'Driving Permit': 'Driving permit number',
        'Other': 'Document number'
    };

    function toggleFields() {
        var purpose = document.getElementById('purpose').value;
        document.getElementById('purpose-other-box').style.display = purpose === 'Other' ? '' : 'none';

        // Once a document is chosen, its number (and, for "Other", its name) become compulsory.
        var idType = document.getElementById('id_document_type').value;
        document.getElementById('id-number-box').style.display = idType ? '' : 'none';
        document.getElementById('id_document_number').required = !!idType;
        document.getElementById('id-number-label').textContent = labels[idType] || 'Document number';

        var other = idType === 'Other';
        document.getElementById('id-other-box').style.display = other ? '' : 'none';
        document.getElementById('id_document_other').required = other;
    }
    document.getElementById('purpose').addEventListener('change', toggleFields);
    document.getElementById('id_document_type').addEventListener('change', toggleFields);
    toggleFields();
</script>
@endsection