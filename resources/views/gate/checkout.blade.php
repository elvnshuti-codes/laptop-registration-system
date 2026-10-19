@extends('layouts.app')

@section('title', 'Visitor Check-Out')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0">Visitors On Site</h4>
            <small class="opacity-75">Checkpoint 1 &middot; Main Gate check-out</small>
        </div>
        <span class="badge bg-light text-dark fs-6">{{ $visits->count() }} on site</span>
    </div>

    <div class="card-body p-4">
        @if ($visits->isEmpty())
            <p class="text-muted mb-0">No visitors are currently on site.</p>
        @else
            <div class="table-responsive">
                <table id="onsite" class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Visit Code</th>
                            <th>Name</th>
                            <th>Card No.</th>
                            <th>Checked In</th>
                            <th>Devices</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($visits as $visit)
                            <tr>
                                <td class="font-monospace fw-semibold">{{ $visit->visit_code }}</td>
                                <td>{{ $visit->name }}</td>
                                <td>{{ $visit->visitor_card_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($visit->gate_checked_in_at)->format('d M, H:i') }}</td>
                                <td>
                                    @if ($visit->devices_on_site > 0)
                                        <span class="badge bg-warning text-dark">{{ $visit->devices_on_site }} still in</span>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('gate.checkout', $visit) }}"
                                          onsubmit="return confirm('Confirm the ID document and visitor card {{ $visit->visitor_card_number }} have been returned?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger"
                                                @disabled($visit->devices_on_site > 0)>
                                            Check Out
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        if ($('#onsite').length) {
            $('#onsite').DataTable({ order: [[3, 'asc']], pageLength: 25 });
        }
    });
</script>
@endsection