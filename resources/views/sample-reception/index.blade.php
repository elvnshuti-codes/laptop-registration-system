@extends('layouts.app')

@section('title', 'Sample Reception')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0">Sample Reception</h4>
                <small class="opacity-75">Visitors on site who brought samples</small>
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

                <div class="row g-3 mb-4 text-center">
                    <div class="col-4">
                        <div class="border rounded p-3">
                            <div class="fs-2 fw-bold">{{ $onSite }}</div>
                            <div class="text-muted small">On site</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-3">
                            <div class="fs-2 fw-bold text-warning">{{ $waiting }}</div>
                            <div class="text-muted small">Waiting</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-3">
                            <div class="fs-2 fw-bold text-success">{{ $served }}</div>
                            <div class="text-muted small">Served</div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('sample.lookup') }}">
                    @csrf
                    <label for="code" class="form-label fw-semibold">Scan the visitor's gate ticket, or type the visit code</label>
                    <div class="input-group">
                        <input type="text" id="code" name="code" class="form-control"
                               placeholder="Scan or type visit code" value="{{ old('code') }}" autofocus>
                        <button type="submit" class="btn btn-primary">Find visitor</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Visitor</th>
                            <th>Samples</th>
                            <th>Owner's company</th>
                            <th>Devices</th>
                            <th>Arrived</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visits as $visit)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $visit->name }}</div>
                                    <div class="text-muted small">{{ $visit->visit_code }}</div>
                                </td>
                                <td>
                                    @foreach ($visit->samples as $sample)
                                        <div class="small">{{ $sample->sample_type }} &times; {{ $sample->sample_count }}</div>
                                    @endforeach
                                </td>
                                <td>{{ $visit->sample_owner_company }}</td>
                                <td>
                                    @if ($visit->devices_on_site > 0)
                                        <span class="badge bg-warning text-dark">{{ $visit->devices_on_site }} on site</span>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($visit->gate_checked_in_at)->format('H:i') }}</td>
                                <td>
                                    @if ($visit->sampleReception)
                                        <span class="badge bg-success">Served</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Waiting</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('sample.visits.show', $visit) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No sample visitors on site right now.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection