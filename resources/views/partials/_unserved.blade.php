@if ($unserved->isNotEmpty())
<div class="card shadow-sm border-0 border-start border-danger border-4 mb-4">
    <div class="card-header bg-danger text-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Left without being served</h5>
            <small class="opacity-75">Entry recorded these visitors as leaving unserved. Acknowledge each one once you have seen it.</small>
        </div>
        <span class="badge bg-light text-dark fs-6">{{ $unserved->count() }}</span>
    </div>
    <div class="card-body p-0">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Visitor</th>
                    <th>Reason</th>
                    <th>Reception contacted</th>
                    <th>Recorded</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($unserved as $ex)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $ex->visit->name }}</div>
                            <div class="text-muted small font-monospace">{{ $ex->visit->visit_code }}</div>
                            @if ($ex->visit->gate_checked_out_at)
                                <span class="badge bg-secondary">Left premises</span>
                            @else
                                <span class="badge bg-warning text-dark">Still on site</span>
                            @endif
                        </td>
                        <td>
                            {{ $ex->reason }}
                            @if ($ex->note)
                                <div class="text-muted small">{{ $ex->note }}</div>
                            @endif
                        </td>
                        <td class="small">
                            @if ($ex->phone_confirmed)
                                Contacted Reception{{ $ex->confirmed_with ? ' (spoke to ' . $ex->confirmed_with . ')' : '' }}
                            @else
                                <span class="text-danger">Not confirmed</span>
                            @endif
                        </td>
                        <td class="small">
                            {{ $ex->recordedBy?->name ?? 'Unknown' }}
                            <div class="text-muted">{{ $ex->created_at->format('d M, H:i') }}</div>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route($ackRoute, $ex) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">Acknowledge</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
