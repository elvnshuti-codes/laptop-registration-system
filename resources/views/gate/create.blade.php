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

                    {{-- Short note for every purpose, Security included. Full details are captured at the reception. --}}
                    <div id="note_block" class="mb-3" style="display: none;">
                        <div id="delivery_wrap" class="mb-3" style="display: none;">
                            <label for="delivery_to" class="form-label fw-semibold">Delivering to</label>
                            <select id="delivery_to" name="delivery_to"
                                    class="form-select @error('delivery_to') is-invalid @enderror">
                                <option value="">Choose...</option>
                                @foreach ($deliveryTo as $key => $text)
                                    <option value="{{ $key }}" @selected(old('delivery_to') === $key)>{{ $text }}</option>
                                @endforeach
                            </select>
                            @error('delivery_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <label for="gate_note" class="form-label fw-semibold">
                            <span id="gate_note_label">Note</span> <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <textarea id="gate_note" name="gate_note" rows="2" maxlength="500"
                                  class="form-control @error('gate_note') is-invalid @enderror">{{ old('gate_note') }}</textarea>
                        @error('gate_note') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="alert alert-info py-2 small mt-2 mb-0">
                            Tell the visitor to go to Reception for clearance. The full details are recorded there.
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
                                    <option value="Other" @selected(old('security_force') === 'Other')>Other (specify)</option>
                                </select>
                                @error('security_force') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <input type="text" id="security_force_other" name="security_force_other" maxlength="80"
                                       class="form-control mt-2 @error('security_force_other') is-invalid @enderror"
                                       value="{{ old('security_force_other') }}" placeholder="Which force or organisation?"
                                       style="display: none;" autocomplete="off">
                                @error('security_force_other') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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

                        {{-- Senior officer in command: some details waived. Never blocks the check-in. --}}
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="has_waiver"
                                   name="has_waiver" value="1" @checked(old('has_waiver'))>
                            <label class="form-check-label fw-semibold" for="has_waiver">
                                Senior officer in command: some details waived
                            </label>
                        </div>

                        <div id="waiver_block" class="border rounded p-3 mb-3 bg-light" style="display: none;">
                            <div class="small text-muted mb-2">
                                The visit is not blocked. What is waived is recorded under your name and reviewed by the Supervisor.
                            </div>

                            <div class="fw-semibold small mb-1">What is waived</div>
                            @foreach ($waiverSteps as $key => $label)
                                <div class="form-check">
                                    <input class="form-check-input waive-step" type="checkbox" name="waive[]" value="{{ $key }}"
                                           id="waive-{{ $key }}" @checked(in_array($key, (array) old('waive', []), true))>
                                    <label class="form-check-label" for="waive-{{ $key }}">{{ $label }}</label>
                                </div>
                            @endforeach
                            @error('waive') <div class="text-danger small">{{ $message }}</div> @enderror

                            <div class="row g-2 mt-2">
                                <div class="col-md-5">
                                    <label for="waiver_reason" class="form-label small mb-1">Reason</label>
                                    <select id="waiver_reason" name="waiver_reason"
                                            class="form-select @error('waiver_reason') is-invalid @enderror">
                                        <option value="">Choose...</option>
                                        @foreach ($waiverReasons as $reason)
                                            <option value="{{ $reason }}" @selected(old('waiver_reason') === $reason)>{{ $reason }}</option>
                                        @endforeach
                                    </select>
                                    @error('waiver_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-7">
                                    <label for="waiver_note" class="form-label small mb-1">Note <span class="text-muted">(optional)</span></label>
                                    <input type="text" id="waiver_note" name="waiver_note" maxlength="300"
                                           class="form-control @error('waiver_note') is-invalid @enderror"
                                           value="{{ old('waiver_note') }}">
                                    @error('waiver_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
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

                            <div class="mt-3">
                                <label for="locker_number" class="form-label fw-semibold">
                                    Locker number <span class="text-muted fw-normal">(where the arms and belongings are kept)</span>
                                </label>
                                <input type="text" id="locker_number" name="locker_number" maxlength="40"
                                       class="form-control @error('locker_number') is-invalid @enderror"
                                       value="{{ old('locker_number') }}" placeholder="e.g. Locker 12">
                                @error('locker_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

    // Mobile number: compulsory for samples and deliveries, so the person can always be reached.
    // The short note and the "delivering to" choice show for every purpose except Security.
    (function () {
        const testing = @json($testingPurposes);
        const delivery = @json($deliveryPurposes);
        const security = @json($securityPurposes);
        const purpose = document.getElementById('purpose');
        const noteBlock = document.getElementById('note_block');
        const deliveryWrap = document.getElementById('delivery_wrap');
        const deliveryTo = document.getElementById('delivery_to');
        const note = document.getElementById('gate_note');
        const mobile = document.getElementById('visitor_mobile_number');
        const mobileHint = document.getElementById('mobile_optional');

        function update() {
            const p = purpose.value;
            const isTesting = testing.includes(p);
            const isDelivery = delivery.includes(p);
            const isSecurity = security.includes(p);

            noteBlock.style.display = p ? '' : 'none';
            document.getElementById('gate_note_label').textContent = isSecurity ? 'Details about the visit' : 'Note';

            deliveryWrap.style.display = isDelivery ? '' : 'none';
            deliveryTo.required = isDelivery;
            if (!isDelivery) deliveryTo.value = '';

            note.placeholder = isSecurity ? 'e.g. Arresting a staff member, escorting a VIP'
                : isTesting ? 'e.g. Which sample, whose sample'
                : (isDelivery ? 'e.g. What is being delivered'
                : 'Anything useful about the visit');

            mobile.required = isTesting || isDelivery;
            mobileHint.style.display = (isTesting || isDelivery) ? 'none' : '';
        }

        purpose.addEventListener('change', update);
        update();
    })();

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
        const forceOther = document.getElementById('security_force_other');
        function updateForceOther() {
            const other = force.value === 'Other';
            forceOther.style.display = other ? '' : 'none';
            if (!other) forceOther.value = '';
        }
        function updateRanks() {
            rankList.innerHTML = '';
            (ranks[force.value] || []).forEach(function (rank) {
                const option = document.createElement('option');
                option.value = rank;
                rankList.appendChild(option);
            });
        }
        function updateArms() { armsBlock.style.display = armsToggle.checked ? '' : 'none'; }

        purpose.addEventListener('change', updateSecurity);
        force.addEventListener('change', updateRanks);
        force.addEventListener('change', updateForceOther);
        armsToggle.addEventListener('change', updateArms);
        updateSecurity(); updateForceOther(); updateRanks(); updateArms();

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
    // Senior officer in command: waived steps are hidden and switched off, never blocking the check-in.
    (function () {
        const toggle = document.getElementById('has_waiver');
        const block = document.getElementById('waiver_block');
        const steps = {
            id_document: function () {
                return [document.getElementById('id_document_type').closest('.row'),
                        document.getElementById('id_document_number').closest('.mb-3')];
            },
            arms: function () { return [document.getElementById('has_arms').closest('.form-check')]; },
        };

        function setHidden(box, hidden) {
            if (!box) return;
            box.style.display = hidden ? 'none' : '';
            box.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = hidden; });
        }

        function update() {
            // Only while the Security section itself is showing.
            const on = toggle.checked && document.getElementById('security_block').style.display !== 'none';
            block.style.display = toggle.checked ? '' : 'none';

            Object.keys(steps).forEach(function (key) {
                const ticked = on && document.getElementById('waive-' + key).checked;

                // Waiving the arms switches the "leaving arms" option off, which also hides the arms rows.
                if (key === 'arms' && ticked) {
                    const armsToggle = document.getElementById('has_arms');
                    armsToggle.checked = false;
                    armsToggle.dispatchEvent(new Event('change'));
                }

                steps[key]().forEach(function (box) { setHidden(box, ticked); });

            });
        }

        toggle.addEventListener('change', update);
        document.getElementById('purpose').addEventListener('change', update);
        document.querySelectorAll('.waive-step').forEach(function (el) { el.addEventListener('change', update); });
        update();
    })();
</script>
@endsection