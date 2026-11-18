@extends('layouts.app')

@section('title', 'Device Requests')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="mb-0">Device requests from Entry</h4>
                <small class="opacity-75">UNBS devices that came back without having been checked out.</small>
            </div>

            <div class="card-body p-0">
                @forelse ($pending as $req)
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">
                                    {{ $req->reference() }} &middot; {{ $req->employee->name }}
                                    <span class="badge bg-warning text-dark">{{ \App\Models\StaffDeviceRequest::TYPES[$req->type] }}</span>
                                </div>
                                <div class="small text-muted">
                                    {{ $req->employee->employee_id_number }} &middot; {{ $req->employee->department }}
                                    &middot; sent by {{ $req->requester->name }} at {{ $req->created_at->format('d M Y, H:i') }}
                                </div>
                                <div class="mt-2">{{ $req->details }}</div>
                                <div class="small text-muted mt-1">The Entry officer confirmed they are keeping the device.</div>
                            </div>

                            <form method="POST" class="text-end" style="min-width: 280px;">
                                @csrf
                                <input type="text" name="decision_note" maxlength="255" class="form-control form-control-sm mb-2"
                                       placeholder="Note (optional)">
                                <button type="submit" formaction="{{ route('admin.device-requests.approve', $req) }}"
                                        class="btn btn-success btn-sm"
                                        onclick="return confirm('Approve {{ $req->reference() }}?');">Approve</button>
                                <button type="submit" formaction="{{ route('admin.device-requests.decline', $req) }}"
                                        class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Decline {{ $req->reference() }}? The device stays held at Entry.');">Decline</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">No request is waiting.</div>
                @endforelse
            </div>
        </div>

        @if ($held->isNotEmpty())
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-semibold text-danger">Held at Entry ({{ $held->count() }})</div>
                <ul class="list-group list-group-flush">
                    @foreach ($held as $req)
                        <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold">{{ $req->reference() }} &middot; {{ $req->employee->name }}</div>
                                <div class="small">{{ $req->details }}</div>
                                <div class="small text-muted">
                                    Declined by {{ $req->decider?->name }} on {{ $req->decided_at->format('d M Y, H:i') }}
                                    @if ($req->decision_note) &mdash; {{ $req->decision_note }} @endif
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.device-requests.release', $req) }}"
                                  onsubmit="return confirm('Mark the device as handed over?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">Mark handed over</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Recent decisions</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 small">
                    <thead><tr><th>Request</th><th>Employee</th><th>Status</th><th>Decided</th></tr></thead>
                    <tbody>
                        @forelse ($recent as $req)
                            <tr>
                                <td>{{ $req->reference() }}<div class="text-muted">{{ \App\Models\StaffDeviceRequest::TYPES[$req->type] }}</div></td>
                                <td>{{ $req->employee->name }}</td>
                                <td>
                                    <span class="badge {{ in_array($req->status, ['approved', 'used']) ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($req->status) }}</span>
                                    @if ($req->released_at) <span class="badge bg-light text-dark border">Handed over</span> @endif
                                </td>
                                <td>
                                    {{ $req->decider?->name }}
                                    <div class="text-muted">{{ $req->decided_at?->format('d M Y, H:i') }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted p-4">Nothing yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection