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
                                Mobile Number <span class="text-muted fw-normal">(optional)</span>
                            </label>
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
</script>
@endsection