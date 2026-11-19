@php
    $rowsOld = old('samples', $visit->samples->isNotEmpty()
        ? $visit->samples->map(fn ($s) => ['type' => $s->sample_type, 'count' => $s->sample_count])->all()
        : [['type' => '', 'count' => '']]);
    $isOpen = ! $visit->samples->isNotEmpty() || $errors->any();
@endphp

<details class="border rounded p-3 mb-4 bg-light" @if ($isOpen) open @endif>
    <summary class="fw-semibold" style="cursor: pointer;">{{ $visit->samples->isNotEmpty() ? 'Change the sample details' : 'Record the sample details' }}</summary>

    @if ($errors->any())
        <div class="alert alert-danger mt-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('sample.visits.intake', $visit) }}" class="mt-3">
        @csrf

        <label class="form-label fw-semibold">Samples</label>
        <div id="sample_rows">
            @foreach ($rowsOld as $i => $row)
                <div class="row g-2 mb-2 sample-row">
                    <div class="col-7">
                        <select name="samples[{{ $i }}][type]" class="form-select" required>
                            <option value="">Sample type...</option>
                            @foreach ($sampleTypes as $type)
                                <option value="{{ $type }}" @selected(($row['type'] ?? '') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3">
                        <input type="number" name="samples[{{ $i }}][count]" min="1" max="9999" class="form-control"
                               value="{{ $row['count'] ?? '' }}" placeholder="How many" required>
                    </div>
                    <div class="col-2 d-grid">
                        <button type="button" class="btn btn-outline-danger remove-sample" title="Remove">&times;</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" id="add_sample" class="btn btn-sm btn-outline-primary mb-3">+ Add another sample type</button>

        <div class="mb-3">
            <label for="delivered_by_type" class="form-label fw-semibold">Who is delivering the samples?</label>
            <select id="delivered_by_type" name="delivered_by_type" class="form-select" required>
                <option value="">Choose...</option>
                @foreach ($deliveredBy as $key => $text)
                    <option value="{{ $key }}" @selected(old('delivered_by_type', $visit->delivered_by_type) === $key)>{{ $text }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3" id="delivered_company_wrap">
            <label for="delivered_by_company" class="form-label fw-semibold">Company / courier</label>
            <input type="text" id="delivered_by_company" name="delivered_by_company" maxlength="255" class="form-control"
                   value="{{ old('delivered_by_company', $visit->delivered_by_company) }}">
        </div>

        <p class="fw-semibold mb-2">Owner of the samples</p>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="sample_owner_company" class="form-label">Owner's company</label>
                <input type="text" id="sample_owner_company" name="sample_owner_company" maxlength="255" class="form-control"
                       value="{{ old('sample_owner_company', $visit->sample_owner_company) }}" required>
            </div>
            <div class="col-md-6">
                <label for="sample_owner_tin" class="form-label">Company TIN <span class="text-muted">(optional)</span></label>
                <input type="text" id="sample_owner_tin" name="sample_owner_tin" maxlength="30" class="form-control text-uppercase"
                       value="{{ old('sample_owner_tin', $visit->sample_owner_tin) }}">
            </div>
        </div>

        <div class="mb-3">
            <label for="sample_owner_name" class="form-label">Owner's name <span class="text-muted">(optional)</span></label>
            <input type="text" id="sample_owner_name" name="sample_owner_name" maxlength="255" class="form-control"
                   value="{{ old('sample_owner_name', $visit->sample_owner_name) }}">
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="sample_owner_phone" class="form-label">Owner's phone</label>
                <input type="tel" id="sample_owner_phone" name="sample_owner_phone" maxlength="20" class="form-control"
                       value="{{ old('sample_owner_phone', $visit->sample_owner_phone) }}">
            </div>
            <div class="col-md-6">
                <label for="sample_owner_email" class="form-label">Owner's email</label>
                <input type="email" id="sample_owner_email" name="sample_owner_email" maxlength="255" class="form-control"
                       value="{{ old('sample_owner_email', $visit->sample_owner_email) }}">
            </div>
            <div class="form-text">Phone or email: at least one.</div>
        </div>

        <div class="mb-3">
            <label for="sample_prn" class="form-label">Payment Reference (PRN) <span class="text-muted">(optional)</span></label>
            <input type="text" id="sample_prn" name="sample_prn" maxlength="50" class="form-control text-uppercase"
                   value="{{ old('sample_prn', $visit->sample_prn) }}">
        </div>

        <button type="submit" class="btn btn-primary">Save sample details</button>
    </form>
</details>

<script>
    (function () {
        const rows = document.getElementById('sample_rows');
        if (!rows) return;

        function reindex() {
            rows.querySelectorAll('.sample-row').forEach(function (row, i) {
                row.querySelector('select').name = 'samples[' + i + '][type]';
                row.querySelector('input').name = 'samples[' + i + '][count]';
            });
        }

        document.getElementById('add_sample').addEventListener('click', function () {
            const clone = rows.querySelector('.sample-row').cloneNode(true);
            clone.querySelector('select').value = '';
            clone.querySelector('input').value = '';
            rows.appendChild(clone);
            reindex();
        });

        rows.addEventListener('click', function (e) {
            const row = e.target.closest('.remove-sample') && e.target.closest('.sample-row');
            if (row && rows.querySelectorAll('.sample-row').length > 1) {
                row.remove();
                reindex();
            }
        });

        const type = document.getElementById('delivered_by_type');
        const wrap = document.getElementById('delivered_company_wrap');
        const company = document.getElementById('delivered_by_company');
        function updateCompany() {
            const needs = ['Company representative', 'Delivery company'].includes(type.value);
            wrap.style.display = needs ? '' : 'none';
            company.required = needs;
            if (!needs) company.value = '';
        }
        type.addEventListener('change', updateCompany);
        updateCompany();
    })();
</script>