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
                            Visitor has a booking? <span class="text-muted fw-normal">(scan or type thecode)</span>
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
                                                    <option value="{{ $type }}" @selected(($row['type']?? '') === $type)>{{ $type }}</option>
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
                                    <option value="{{ $value }}" @selected(old('delivered_by_type') ===$value)>{{ $label }}</option>
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
                            <div class="form-text">The delivery person may not know it. If the owner isthe visitor, leave blank.</div>
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
                                   value="{{ old('sample_prn') }}" maxlength="50" placeholder="PRN fromthe proof of payment">
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
                                    class="form-select @error('id_document_type') is-invalid @enderror"required>
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
                                   value="{{ old('id_document_other', $booking?->id_document_other) }}"placeholder="Document name">
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
                        @error('id_document_number') <div class="invalid-feedback">{{ $message }}</div>@enderror
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

                    {{-- Security officer details --}}
                    <div id="security_block" class="border border-warning rounded p-3 mb-3" style="display: none;">
                        <h6 class="fw-bold mb-3">Security officer details</h6>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="security_force" class="form-label fw-semibold">Force</label>
                                <select id="security_force" name="security_force"
                                        class="form-select @error('security_force') is-invalid @enderror">
                                    <option value="">Choose...</option>
                                    @foreach ($forces as $value => $label)
                                        <option value="{{ $value }}" @selected(old('security_force') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('security_force') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="officer_rank" class="form-label fw-semibold">Officer's rank</label>
                                <input type="text" id="officer_rank" name="officer_rank" list="rank_list" maxlength="80"
                                       class="form-control @error('officer_rank') is-invalid @enderror"
                                       value="{{ old('officer_rank') }}" placeholder="Choose or type the rank" autocomplete="off">
                                <datalist id="rank_list"></datalist>
                                @error('officer_rank') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="vip_name" class="form-label fw-semibold">VIP being protected</label>
                                <input type="text" id="vip_name" name="vip_name" maxlength="255"
                                       class="form-control @error('vip_name') is-invalid @enderror"
                                       value="{{ old('vip_name') }}" placeholder="Name of the VIP">
                                @error('vip_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="vip_title" class="form-label fw-semibold">VIP's title</label>
                                <input type="text" id="vip_title" name="vip_title" maxlength="255"
                                       class="form-control @error('vip_title') is-invalid @enderror"
                                       value="{{ old('vip_title') }}" placeholder="e.g. Minister of Trade">
                                @error('vip_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="locker_number" class="form-label fw-semibold">
                                Locker number <span class="text-muted fw-normal">(optional)</span>
                            </label>
                            <input type="text" id="locker_number" name="locker_number" maxlength="40"
                                   class="form-control @error('locker_number') is-invalid @enderror"
                                   value="{{ old('locker_number') }}" placeholder="Locker holding the officer's belongings">
                            @error('locker_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="has_arms"
                                   name="has_arms" value="1" @checked(old('has_arms', ! $errors->any()))>
                            <label class="form-check-label fw-semibold" for="has_arms">
                                Officer is leaving arms at the gate
                            </label>
                        </div>

                        <div id="arms_block" style="display: none;">
                            <div id="arm_rows">
                                @foreach (old('arms', [['type' => '', 'make' => '', 'serial' => '', 'magazines' => '', 'count' => '']]) as $i => $row)
                                    <div class="border rounded p-2 mb-2 arm-row">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label small mb-1">Type</label>
                                                <select name="arms[{{ $i }}][type]"
                                                        class="form-select arm-type @error("arms.$i.type") is-invalid @enderror">
                                                    <option value="">Choose...</option>
                                                    @foreach ($armTypes as $group => $types)
                                                        <optgroup label="{{ $group }}">
                                                            @foreach ($types as $type)
                                                                <option value="{{ $type }}" @selected(($row['type'] ?? '') === $type)>{{ $type }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label small mb-1 arm-make-label">Make / model (optional)</label>
                                                <input type="text" name="arms[{{ $i }}][make]" value="{{ $row['make'] ?? '' }}" maxlength="120"
                                                       class="form-control arm-make @error("arms.$i.make") is-invalid @enderror">
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-0 align-items-end">
                                            <div class="col-md-5">
                                                <label class="form-label small mb-1 arm-serial-label">Serial number</label>
                                                <input type="text" name="arms[{{ $i }}][serial]" value="{{ $row['serial'] ?? '' }}" maxlength="60"
                                                       class="form-control text-uppercase @error("arms.$i.serial") is-invalid @enderror">
                                            </div>
                                            <div class="col-md-3 arm-mags-wrap">
                                                <label class="form-label small mb-1">Magazines</label>
                                                <input type="number" min="0" max="99" name="arms[{{ $i }}][magazines]" value="{{ $row['magazines'] ?? '' }}"
                                                       class="form-control">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1 arm-count-label">Bullets</label>
                                                <input type="number" min="0" max="9999" name="arms[{{ $i }}][count]" value="{{ $row['count'] ?? '' }}"
                                                       class="form-control @error("arms.$i.count") is-invalid @enderror">
                                            </div>
                                            <div class="col-md-1 d-grid">
                                                <button type="button" class="btn btn-outline-danger remove-arm" title="Remove">&times;</button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" id="add_arm" class="btn btn-sm btn-outline-primary">+ Add another weapon</button>
                        </div>

                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>Locker key:</strong> put a card numbered
                            <strong id="locker_card_no">(visitor card number)</strong>
                            on the key holder of the locker where this officer's belongings have been placed.
                            <div class="small mt-1">
                                Handled by <strong>{{ auth()->user()->name }}</strong>.
                                Your name is recorded and printed on the ticket.
                            </div>
                        </div>
                    </div>

                    {{-- Arrival --}}
                    <div class="border rounded p-3 mb-3">
                        <h6 class="fw-bold text-primary mb-3">Arrival</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="arrival_mode" class="form-label fw-semibold">How did the visitor arrive?</label>
                                <select id="arrival_mode" name="arrival_mode"
                                        class="form-select @error('arrival_mode') is-invalid @enderror" required>
                                    <option value="">Choose...</option>
                                    @foreach ($arrivalModes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('arrival_mode') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('arrival_mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6" id="plate_wrap">
                                <label for="number_plate" class="form-label fw-semibold"><span id="plate_label">Number plate</span></label>
                                <input type="text" id="number_plate" name="number_plate" maxlength="40"
                                       class="form-control text-uppercase @error('number_plate') is-invalid @enderror"
                                       value="{{ old('number_plate') }}" placeholder="e.g. UBA 123X">
                                @error('number_plate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Belongings left at the gate --}}
                    <div class="border rounded p-3 mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="has_left_items"
                                   name="has_left_items" value="1" @checked(old('has_left_items'))>
                            <label class="form-check-label fw-semibold" for="has_left_items">
                                Visitor is leaving belongings at the gate
                            </label>
                        </div>

                        <div id="left_items_block" style="display: none;" class="mt-3">
                            <div id="left_item_rows">
                                @foreach (old('items', [['name' => '', 'colour' => '', 'quantity' => '', 'notes' => '']]) as $i => $row)
                                    <div class="row g-2 mb-2 left-item-row">
                                        <div class="col-md-4">
                                            <input type="text" name="items[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}"
                                                   class="form-control @error("items.$i.name") is-invalid @enderror" placeholder="Item, e.g. Laptop bag">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="items[{{ $i }}][colour]" value="{{ $row['colour'] ?? '' }}"
                                                   class="form-control @error("items.$i.colour") is-invalid @enderror" placeholder="Colour">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" min="1" max="999" name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? '' }}"
                                                   class="form-control @error("items.$i.quantity") is-invalid @enderror" placeholder="No.">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="items[{{ $i }}][notes]" value="{{ $row['notes'] ?? '' }}"
                                                   class="form-control" placeholder="Notes (optional)">
                                        </div>
                                        <div class="col-md-1 d-grid">
                                            <button type="button" class="btn btn-outline-danger remove-left-item" title="Remove">&times;</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" id="add_left_item" class="btn btn-sm btn-outline-primary">+ Add another item</button>

                            <div class="alert alert-secondary mt-3 mb-0 small">
                                Handled by <strong>{{ auth()->user()->name }}</strong>.
                                Your name is recorded and printed on the visitor's ticket.
                            </div>
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

    // Arrival: the plate box depends on how the visitor arrived.
    (function () {
        const mode = document.getElementById('arrival_mode');
        const plateWrap = document.getElementById('plate_wrap');
        const plateInput = document.getElementById('number_plate');
        const plateLabel = document.getElementById('plate_label');
        const plateLabels = {
            vehicle: 'Vehicle number plate',
            motorcycle: 'Motorcycle number plate',
            bicycle: 'Bicycle number plate or details (optional)',
        };

        function updateArrival() {
            const asks = mode.value in plateLabels;
            plateWrap.style.display = asks ? '' : 'none';

            if (asks) {
                plateLabel.textContent = plateLabels[mode.value];
            } else {
                plateInput.value = '';
            }
        }

        mode.addEventListener('change', updateArrival);
        updateArrival();

        // Belongings left at the gate.
        const toggle = document.getElementById('has_left_items');
        const block = document.getElementById('left_items_block');
        const rowsBox = document.getElementById('left_item_rows');

        function updateLeft() { block.style.display = toggle.checked ? '' : 'none'; }
        toggle.addEventListener('change', updateLeft);
        updateLeft();

        function reindex() {
            rowsBox.querySelectorAll('.left-item-row').forEach(function (row, i) {
                row.querySelectorAll('input').forEach(function (input) {
                    input.name = input.name.replace(/items\[\d+\]/, 'items[' + i + ']');
                });
            });
        }

        document.getElementById('add_left_item').addEventListener('click', function () {
            const clone = rowsBox.querySelector('.left-item-row').cloneNode(true);
            clone.querySelectorAll('input').forEach(function (input) { input.value = ''; input.classList.remove('is-invalid'); });
            rowsBox.appendChild(clone);
            reindex();
        });

        rowsBox.addEventListener('click', function (e) {
            const row = e.target.closest('.remove-left-item') && e.target.closest('.left-item-row');
            if (!row) return;

            if (rowsBox.querySelectorAll('.left-item-row').length > 1) {
                row.remove();
                reindex();
            } else {
                row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
            }
        });
    })();

    // Security purpose: show the officer section, rank suggestions and arms rows.
    (function () {
        const securityPurposes = @json($securityPurposes);
        const ranks = @json($ranks);
        const purpose = document.getElementById('purpose');
        const block = document.getElementById('security_block');
        const force = document.getElementById('security_force');
        const rankList = document.getElementById('rank_list');
        const armsToggle = document.getElementById('has_arms');
        const armsBlock = document.getElementById('arms_block');
        const armRows = document.getElementById('arm_rows');
        const cardInput = document.getElementById('visitor_card_number');
        const cardNo = document.getElementById('locker_card_no');

        const firearms = @json($armTypes['Firearms']);

        // Labels and boxes change with the weapon type.
        function adjustArm(row) {
            const type = row.querySelector('.arm-type').value;
            const firearm = firearms.includes(type);
            const other = type === 'Other';
            row.querySelector('.arm-make-label').textContent = other ? 'Describe the weapon (required)' : 'Make / model (optional)';
            row.querySelector('.arm-serial-label').textContent = firearm ? 'Serial number' : 'Serial number (if any)';
            row.querySelector('.arm-count-label').textContent = firearm ? 'Bullets' : 'Quantity';
            const mags = row.querySelector('.arm-mags-wrap');
            mags.style.display = firearm || type === '' ? '' : 'none';
            if (!firearm && type !== '') mags.querySelector('input').value = '';
        }

        function updateSecurity() { block.style.display = securityPurposes.includes(purpose.value) ? '' : 'none'; }
        function updateRanks() {
            rankList.innerHTML = '';
            (ranks[force.value] || []).forEach(function (rank) {
                const option = document.createElement('option');
                option.value = rank;
                rankList.appendChild(option);
            });
        }
        function updateArms() { armsBlock.style.display = armsToggle.checked ? '' : 'none'; }
        function updateCard() { cardNo.textContent = cardInput.value.trim() || '(visitor card number)'; }

        purpose.addEventListener('change', updateSecurity);
        force.addEventListener('change', updateRanks);
        armsToggle.addEventListener('change', updateArms);
        cardInput.addEventListener('input', updateCard);
        updateSecurity(); updateRanks(); updateArms(); updateCard();

        function reindexArms() {
            armRows.querySelectorAll('.arm-row').forEach(function (row, i) {
                row.querySelectorAll('select, input').forEach(function (el) {
                    el.name = el.name.replace(/arms\[\d+\]/, 'arms[' + i + ']');
                });
            });
        }

        document.getElementById('add_arm').addEventListener('click', function () {
            const clone = armRows.querySelector('.arm-row').cloneNode(true);
            clone.querySelectorAll('input').forEach(function (el) { el.value = ''; });
            clone.querySelectorAll('select').forEach(function (el) { el.value = ''; });
            clone.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            armRows.appendChild(clone);
            reindexArms();
            adjustArm(clone);
        });

        armRows.addEventListener('change', function (e) {
            if (e.target.classList.contains('arm-type')) adjustArm(e.target.closest('.arm-row'));
        });
        armRows.querySelectorAll('.arm-row').forEach(adjustArm);

        armRows.addEventListener('click', function (e) {
            const row = e.target.closest('.remove-arm') && e.target.closest('.arm-row');
            if (!row) return;

            if (armRows.querySelectorAll('.arm-row').length > 1) {
                row.remove();
                reindexArms();
            } else {
                row.querySelectorAll('input').forEach(function (el) { el.value = ''; });
                row.querySelectorAll('select').forEach(function (el) { el.value = ''; });
                adjustArm(row);
            }
        });
    })();
</script>
@endsection