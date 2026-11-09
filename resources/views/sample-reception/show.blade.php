@extends('layouts.app')

@section('title', 'Sample Reception · ' . $visit->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10">

        <a href="{{ route('sample.index') }}" class="btn btn-sm btn-outline-secondary mb-3">&larr; Back to dashboard</a>

        <div class="row g-4">

            {{-- Details --}}
            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white py-3">
                        <h5 class="mb-0">{{ $visit->name }}</h5>
                        <small class="opacity-75">{{ $visit->visit_code }}</small>
                    </div>
                    <div class="card-body p-4">

                        <h6 class="fw-bold text-primary">Samples</h6>
                        <table class="table table-sm mb-4">
                            @foreach ($visit->samples as $sample)
                                <tr>
                                    <td>{{ $sample->sample_type }}</td>
                                    <td class="text-end fw-semibold">{{ $sample->sample_count }}</td>
                                </tr>
                            @endforeach
                        </table>

                        <h6 class="fw-bold text-primary">Owner</h6>
                        <table class="table table-sm mb-4">
                            <tr><th class="w-50 fw-normal text-muted">Company</th><td>{{ $visit->sample_owner_company }}</td></tr>
                            <tr><th class="fw-normal text-muted">Company TIN</th><td>{{ $visit->sample_owner_tin ?? '—' }}</td></tr>
                            <tr><th class="fw-normal text-muted">Name</th><td>{{ $visit->sample_owner_name ?? '—' }}</td></tr>
                            <tr><th class="fw-normal text-muted">Phone</th><td>{{ $visit->sample_owner_phone ?? '—' }}</td></tr>
                            <tr><th class="fw-normal text-muted">Email</th><td>{{ $visit->sample_owner_email ?? '—' }}</td></tr>
                            <tr><th class="fw-normal text-muted">Payment Reference (PRN)</th><td>{{ $visit->sample_prn ?? '—' }}</td></tr>
                        </table>

                        <h6 class="fw-bold text-primary">Delivered by</h6>
                        <table class="table table-sm mb-4">
                            <tr>
                                <th class="w-50 fw-normal text-muted">Type</th>
                                <td>{{ \App\Http\Controllers\GateController::DELIVERED_BY[$visit->delivered_by_type] ?? $visit->delivered_by_type }}</td>
                            </tr>
                            <tr><th class="fw-normal text-muted">Company / courier</th><td>{{ $visit->delivered_by_company ?? '—' }}</td></tr>
                            <tr><th class="fw-normal text-muted">Visitor card</th><td>{{ $visit->visitor_card_number }}</td></tr>
                            <tr><th class="fw-normal text-muted">Mobile</th><td>{{ $visit->visitor_mobile_number ?? '—' }}</td></tr>
                            <tr>
                                <th class="fw-normal text-muted">Arrived</th>
                                <td>{{ \Carbon\Carbon::parse($visit->gate_checked_in_at)->format('d M Y, H:i') }}</td>
                            </tr>
                        </table>

                        <h6 class="fw-bold text-primary">Devices</h6>
                        @forelse ($visit->devices as $device)
                            <div class="small">
                                {{ $device->description }} <span class="text-muted">({{ $device->device_code }})</span>
                                @if ($device->device_checked_out_at)
                                    <span class="badge bg-secondary">Checked out</span>
                                @else
                                    <span class="badge bg-warning text-dark">On site</span>
                                @endif
                            </div>
                        @empty
                            <div class="text-muted small">No device registered.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Serve --}}
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0">Service</h5>
                    </div>
                    <div class="card-body p-4">

                        @if ($visit->sampleReception)
                            <div class="alert alert-success">
                                <strong>Served</strong> at {{ $visit->sampleReception->served_at->format('d M Y, H:i') }}
                                @if ($visit->sampleReception->servedBy)
                                    by {{ $visit->sampleReception->servedBy->name }}
                                @endif
                            </div>
                            <p class="mb-1"><span class="text-muted">Reference number:</span>
                                {{ $visit->sampleReception->reference_number ?? '—' }}</p>
                            <p class="mb-0"><span class="text-muted">Note:</span>
                                {{ $visit->sampleReception->note ?? '—' }}</p>
                        @else
                            <form method="POST" action="{{ route('sample.visits.serve', $visit) }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="reference_number" class="form-label fw-semibold">
                                        Reference number <span class="text-muted fw-normal">(optional)</span>
                                    </label>
                                    <input type="text" id="reference_number" name="reference_number" maxlength="50"
                                           class="form-control text-uppercase @error('reference_number') is-invalid @enderror"
                                           value="{{ old('reference_number') }}" placeholder="From the existing sample system">
                                    @error('reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="note" class="form-label fw-semibold">
                                        Note <span class="text-muted fw-normal">(optional)</span>
                                    </label>
                                    <textarea id="note" name="note" rows="3" maxlength="500"
                                              class="form-control @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                                    @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Mark as served</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection