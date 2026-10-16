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

                <form method="POST" action="{{ route('gate.store') }}" novalidate>
                    @csrf

                    {{-- Visitor name --}}
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Visitor Name</label>
                        <input type="text" id="name" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="Full name" autofocus required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Purpose --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="purpose" class="form-label fw-semibold">Purpose of Visit</label>
                            <select id="purpose" name="purpose"
                                    class="form-select @error('purpose') is-invalid @enderror" required>
                                @foreach (['Meeting', 'Delivery', 'Interview', 'Official Business', 'Other'] as $option)
                                    <option value="{{ $option }}" @selected(old('purpose', 'Meeting') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6" id="purpose_other_wrap">
                            <label for="purpose_other" class="form-label fw-semibold">Please specify</label>
                            <input type="text" id="purpose_other" name="purpose_other"
                                   class="form-control @error('purpose_other') is-invalid @enderror"
                                   value="{{ old('purpose_other') }}" placeholder="Describe the purpose">
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
                                    <option value="{{ $option }}" @selected(old('id_document_type', 'National ID') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('id_document_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6" id="id_other_wrap">
                            <label for="id_document_other" class="form-label fw-semibold">Please specify</label>
                            <input type="text" id="id_document_other" name="id_document_other"
                                   class="form-control @error('id_document_other') is-invalid @enderror"
                                   value="{{ old('id_document_other') }}" placeholder="Document name">
                            @error('id_document_other') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
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
                                   value="{{ old('visitor_mobile_number') }}" placeholder="07XX XXX XXX">
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
</script>
@endsection