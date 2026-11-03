@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0">Visitor Bookings</h4>
            <small class="opacity-75">Expected visitors, from the last 7 days onward</small>
        </div>
        <div>
            <a href="{{ route('reception.index') }}" class="btn btn-light btn-sm">Back to reception</a>
            <a href="{{ route('reception.bookings.create') }}" class="btn btn-warning btn-sm">Pre-book a visitor</a>
        </div>
    </div>

    <div class="card-body p-4">
        @if ($bookings->isEmpty())
            <p class="text-muted mb-0">No bookings yet.</p>
        @else
            <div class="table-responsive">
                <table id="bookings" class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Visitor</th>
                            <th>Meeting with</th>
                            <th>Expected</th>
                            <th>Status</th>
                            <th>Booked by</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $b)
                            @php $expired = $b->status === 'expected' && $b->expected_date->lt(today()); @endphp
                            <tr>
                                <td class="font-monospace fw-semibold">
                                    <a href="{{ route('reception.bookings.show', $b) }}" class="text-decoration-none">{{ $b->booking_code }}</a>
                                </td>
                                <td>{{ $b->name }}</td>
                                <td>{{ $b->meeting_with }}</td>
                                <td data-order="{{ $b->expected_date->format('Y-m-d') }} {{ $b->expected_time }}">
                                    {{ $b->expected_date->format('D, d M') }}@if ($b->expected_time), {{ substr($b->expected_time, 0, 5) }}@endif
                                </td>
                                <td>
                                    @if ($b->status === 'arrived')
                                        <span class="badge bg-success">Arrived</span>
                                    @elseif ($b->status === 'cancelled')
                                        <span class="badge bg-secondary">Cancelled</span>
                                    @elseif ($expired)
                                        <span class="badge bg-dark">Expired</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Expected</span>
                                    @endif
                                </td>
                                <td>{{ $b->createdBy->name ?? '—' }}</td>
                                <td class="text-end">
                                    @if ($b->status === 'expected' && ! $expired)
                                        <form method="POST" action="{{ route('reception.bookings.cancel', $b) }}" class="d-inline"
                                              onsubmit="return confirm('Cancel booking {{ $b->booking_code }} for {{ $b->name }}?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                        </form>
                                    @endif
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
        if ($('#bookings').length) {
            $('#bookings').DataTable({ order: [[3, 'asc']], pageLength: 25 });
        }
    });
</script>
@endsection