@extends('layouts.app')

@section('title', 'Visitor Check-In')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0">Visitor Check-In</h4>
                <small class="opacity-75">Checkpoint 1 &middot; Main Gate</small>
            </div>

            <div class="card-body p-4">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Please fix the following:</strong>
                        <ul class="mb-0 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Booking lookup (only for visitors who were pre-booked) --}}
                @if (! $booking)
                    <form method="GET" action="{{ route('gate.booking.lookup') }}" class="mb-4 pb-4 border-bottom">
                        <label for="booking_code" class="form-label fw-semibold">
                            Visitor has a booking? <span class="text-muted fw-normal">(scan or type the code)</span>
                        </label>
                        <div class="input-group">
                            <input type="text" id="booking_code" name="booking_code"
                                   class="form-control text-uppercase" placeholder="BK-XXXXXX"
                                   value="{{ old('booking_code') }}">
                            <button type="submit" class="btn btn-outline-primary">Find booking</button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-success">
                        <strong>Booking {{ $booking->booking_code }} found.</strong>
                        Details are filled in below. <a href="{{ route('gate.create') }}" class="alert-link">Clear</a>
                    </div>
                    <div class="alert alert-warning">
                        Check that the visitor's <strong>{{ $booking->id_document_type === 'Other' ? $booking->id_document_other : $booking->id_document_type }}</strong>
                        shows number <strong>{{ $booking->id_document_number }}</strong> before you continue.
                    </div>
                @endif

                <form method="POST" action="{{ route('gate.store') }}" novalidate>
                    @csrf

                    @if ($booking)
                        <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                    @endif

                    {{-- Visitor name --}}
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Visitor Name</label>
                        <input type="text" id="name" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $booking?->name) }}" placeholder="Full name" autofocus required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Purpose --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="purpose" class="form-label fw-semibold">Purpose of Visit</label>
                            <select id="purpose" name="purpose"
                                    class="form-select @error('purpose') is-invalid @enderror" required>
                                @foreach ($purposes as $option)
                                    <option value="{{ $option }}" @selected(old('purpose', $booking?->purpose ?? 'Meeting') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6" id="purpose_other_wrap">
                            <label for="purpose_other" class="form-label fw-semibold">Please specify</label>
                            <input type="text" id="purpose_other" name="purpose_other"
                                   class="form-control @error('purpose_other') is-invalid @enderror"
                                   value="{{ old('purpose_other', $booking?->purpose_other) }}" placeholder="Describe the purpose">
                            @error('purpose_other') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Sample details (only for purposes that go to Sample Reception) --}}
                    <div id="sample_block" class="border rounded p-3 mb-3 bg-light" style="display:none;">
                        <h6 class="fw-bold text-primary mb-3">Sample details</h6>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Samples</label>
                            <div id="sample_rows">
                                @foreach (old('samples', [['type' => '', 'count' => '']]) as $i => $row)
                                    <div class="row g-2 mb-2 sample-row">
                                        <div class="col-7">
                                            <select name="samples[{{ $i }}][type]"
                                                    class="form-select @error("samples.$i.type") is-invalid @enderror">
                                                <option value="">Sample type...</option>
                                                @foreach ($sampleTypes as $type)
                                                    <option value="{{ $type }}" @selected(($row['type'] ?? '') === $type)>{{ $type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <input type="number" name="samples[{{ $i }}][count]" min="1" max="9999"
                                                   class="form-control @error("samples.$i.count") is-invalid @enderror"
                                                   value="{{ $row['count'] ?? '' }}" placeholder="No.">
                                        </div>
                                        <div class="col-2 d-grid">
                                            <button type="button" class="btn btn-outline-danger remove-sample" title="Remove">&times;</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" id="add_sample" class="btn btn-sm btn-outline-primary">+ Add another sample type</button>
                        </div>

                        <div class="mb-3">
                            <label for="delivered_by_type" class="form-label fw-semibold">Who is delivering the samples?</label>
                            <select id="delivered_by_type" name="delivered_by_type"
                                    class="form-select @error('delivered_by_type') is-invalid @enderror">
                                <option value="">Select...</option>
                                @foreach ($deliveredBy as $value => $label)
                                    <option value="{{ $value }}" @selected(old('delivered_by_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('delivered_by_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">The person at the gate is the one delivering. Their name and mobile are captured above.</div>
                        </div>

                        <div class="mb-3" id="delivered_company_wrap">
                            <label for="delivered_by_company" class="form-label fw-semibold">Company / Courier Name</label>
                            <input type="text" id="delivered_by_company" name="delivered_by_company"
                                   class="form-control @error('delivered_by_company') is-invalid @enderror"
                                   value="{{ old('delivered_by_company') }}" placeholder="Company or courier the deliverer works for">
                            @error('delivered_by_company') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <hr>
                        <p class="fw-semibold mb-2">Owner of the samples</p>

                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label for="sample_owner_company" class="form-label fw-semibold">Owner's Company</label>
                                <input type="text" id="sample_owner_company" name="sample_owner_company"
                                       class="form-control @error('sample_owner_company') is-invalid @enderror"
                                       value="{{ old('sample_owner_company') }}" placeholder="Company the samples belong to">
                                @error('sample_owner_company') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label for="sample_owner_tin" class="form-label fw-semibold">
                                    Company TIN <span class="text-muted fw-normal">(optional)</span>
                                </label>
                                <input type="text" id="sample_owner_tin" name="sample_owner_tin"
                                       class="form-control text-uppercase @error('sample_owner_tin') is-invalid @enderror"
                                       value="{{ old('sample_owner_tin') }}" maxlength="30">
                                @error('sample_owner_tin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="sample_owner_name" class="form-label fw-semibold">
                                Owner's Name <span class="text-muted fw-normal">(optional)</span>
                            </label>
                            <input type="text" id="sample_owner_name" name="sample_owner_name"
                                   class="form-control @error('sample_owner_name') is-invalid @enderror"
                                   value="{{ old('sample_owner_name') }}" placeholder="Full name">
                            @error('sample_owner_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">The delivery person may not know it. If the owner is the visitor, leave blank.</div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="sample_owner_phone" class="form-label fw-semibold">Owner's Phone</label>
                                <input type="tel" id="sample_owner_phone" name="sample_owner_phone"
                                       class="form-control @error('sample_owner_phone') is-invalid @enderror"
                                       value="{{ old('sample_owner_phone') }}" placeholder="07XX XXX XXX">
                                @error('sample_owner_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="sample_owner_email" class="form-label fw-semibold">Owner's Email</label>
                                <input type="email" id="sample_owner_email" name="sample_owner_email"
                                       class="form-control @error('sample_owner_email') is-invalid @enderror"
                                       value="{{ old('sample_owner_email') }}" placeholder="name@company.com">
                                @error('sample_owner_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-text mt-0">Enter at least one: phone or email.</div>
                            </div>
                        </div>

                        <hr>
                        <div class="mb-0">
                            <label for="sample_prn" class="form-label fw-semibold">
                                Payment Reference Number (PRN) <span class="text-muted fw-normal">(optional)</span>
                            </label>
                            <input type="text" id="sample_prn" name="sample_prn"
                                   class="form-control text-uppercase @error('sample_prn') is-invalid @enderror"
                                   value="{{ old('sample_prn') }}" maxlength="50" placeholder="PRN from the proof of payment">
                            @error('sample_prn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">
                                The PRN is generated when payment is made and is the proof of payment. It links to the
                                owner of the samples, and the company TIN links to the company that owns the samples
                                and paid for them.
                            </div>
                        </div>
                    </div>  

                    {{-- ID document --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="id_document_type" class="form-label fw-semibold">ID Document Type</label>
                            <select id="id_document_type" name="id_document_type"
                                    class="form-select @error('id_document_type') is-invalid @enderror" required>
                                @foreach (['National ID', 'Passport', 'Driving Permit', 'Other'] as $option)
                                    <option value="{{ $option }}" @selected(old('id_document_type', $booking?->id_document_type ?? 'National ID') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('id_document_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6" id="id_other_wrap">
                            <label for="id_document_other" class="form-label fw-semibold">Please specify</label>
                            <input type="text" id="id_document_other" name="id_document_other"
                                   class="form-control @error('id_document_other') is-invalid @enderror"
                                   value="{{ old('id_document_other', $booking?->id_document_other) }}" placeholder="Document name">
                            @error('id_document_other') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- ID document number --}}
                    <div class="mb-3">
                        <label for="id_document_number" class="form-label fw-semibold">
                            <span id="id_number_label">ID Document</span> Number
                        </label>
                        <input type="text" id="id_document_number" name="id_document_number"
                               class="form-control text-uppercase @error('id_document_number') is-invalid @enderror"
                               value="{{ old('id_document_number', $booking?->id_document_number) }}"
                               placeholder="As shown on the document" required>
                        @error('id_document_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Card number + mobile --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="visitor_card_number" class="form-label fw-semibold">Visitor Card Number</label>
                            <input type="text" id="visitor_card_number" name="visitor_card_number"
                                   class="form-control @error('visitor_card_number') is-invalid @enderror"
                                   value="{{ old('visitor_card_number') }}" placeholder="e.g. 0023" required>
                            @error('visitor_card_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                        <label for="visitor_mobile_number" class="form-label fw-semibold">
                                Mobile Number <span id="mobile_optional" class="text-muted fw-normal">(optional)</span></label>
                            <input type="tel" id="visitor_mobile_number" name="visitor_mobile_number"
                                   class="form-control @error('visitor_mobile_number') is-invalid @enderror"
                                   value="{{ old('visitor_mobile_number', $booking?->visitor_mobile_number) }}" placeholder="07XX XXX XXX">
                            @error('visitor_mobile_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Check In Visitor</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    // Show each "Please specify" box only when "Other" is chosen.
    function toggleOther(selectId, wrapId, inputId) {
        const select = document.getElementById(selectId);
        const wrap = document.getElementById(wrapId);
        const input = document.getElementById(inputId);

        function update() {
            const isOther = select.value === 'Other';
            wrap.style.display = isOther ? '' : 'none';
            if (!isOther) input.value = '';
        }
        select.addEventListener('change', update);
        update();
    }

    toggleOther('purpose', 'purpose_other_wrap', 'purpose_other');
    toggleOther('id_document_type', 'id_other_wrap', 'id_document_other');

    // The ID number label follows the chosen document type.
    const idType = document.getElementById('id_document_type');
    const idLabel = document.getElementById('id_number_label');
    function updateIdLabel() {
        idLabel.textContent = idType.value === 'Other' ? 'ID Document' : idType.value;
    }
    idType.addEventListener('change', updateIdLabel);
    updateIdLabel();

    // Sample details appear only for purposes that go to Sample Reception.
    const testingPurposes = @json($testingPurposes);
    const purposeSelect = document.getElementById('purpose');
    const sampleBlock = document.getElementById('sample_block');
    function updateSampleBlock() {
        const isTesting = testingPurposes.includes(purposeSelect.value);
        sampleBlock.style.display = isTesting ? '' : 'none';
        document.getElementById('mobile_optional').style.display = isTesting ? 'none' : '';
    }
    purposeSelect.addEventListener('change', updateSampleBlock);
    updateSampleBlock();

    // The company/courier box only matters for company or courier deliveries.
    const deliveredType = document.getElementById('delivered_by_type');
    const companyWrap = document.getElementById('delivered_company_wrap');
    const companyInput = document.getElementById('delivered_by_company');
    function updateDeliveredCompany() {
        const needs = ['Company representative', 'Delivery company'].includes(deliveredType.value);
        companyWrap.style.display = needs ? '' : 'none';
        if (!needs) companyInput.value = '';
    }
    deliveredType.addEventListener('change', updateDeliveredCompany);
    updateDeliveredCompany();

        // Several sample types: add and remove rows.
        const sampleRows = document.getElementById('sample_rows');
    function reindexSamples() {
        sampleRows.querySelectorAll('.sample-row').forEach((row, i) => {
            row.querySelector('select').name = `samples[${i}][type]`;
            row.querySelector('input').name = `samples[${i}][count]`;
        });
    }
    document.getElementById('add_sample').addEventListener('click', () => {
        const clone = sampleRows.querySelector('.sample-row').cloneNode(true);
        clone.querySelector('select').value = '';
        clone.querySelector('input').value = '';
        clone.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        sampleRows.appendChild(clone);
        reindexSamples();
    });
    sampleRows.addEventListener('click', (e) => {
        const row = e.target.closest('.remove-sample') && e.target.closest('.sample-row');
        if (row && sampleRows.querySelectorAll('.sample-row').length > 1) {
            row.remove();
            reindexSamples();
        }
    });

    // If the owner is delivering, their phone is the visitor's mobile: fill it automatically.
    const mobileInput = document.getElementById('visitor_mobile_number');
    const ownerPhone = document.getElementById('sample_owner_phone');
    let lastMirrored = '';
    function mirrorOwnerPhone() {
        if (deliveredType.value === 'Owner' && (ownerPhone.value === '' || ownerPhone.value === lastMirrored)) {
            ownerPhone.value = mobileInput.value;
            lastMirrored = mobileInput.value;
        }
    }
    mobileInput.addEventListener('input', mirrorOwnerPhone);
    deliveredType.addEventListener('change', mirrorOwnerPhone);
</script>
@endsection