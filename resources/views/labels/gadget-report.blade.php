@extends('layouts.app')

@section('title', 'Walk-in Gadgets Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Walk-in gadgets</h4>
        <small class="text-muted">Personal gadgets registered at Entry with a pre-printed sticker &middot; {{ $gadgets->count() }} in total</small>
    </div>
    <a href="javascript:history.back()" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card shadow-sm border-0">
    @if ($gadgets->isEmpty())
        <div class="card-body text-muted">No walk-in gadget has been registered yet.</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Owner</th><th>Gadget</th><th>Colour</th><th>Seal no.</th>
                        <th>Sticker</th><th>Registered</th><th>Check-ins</th><th>Last in</th><th>Now</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($gadgets as $gadget)
                        @php $v = $visits->get($gadget->id); @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $gadget->employee->name }}</div>
                                <div class="small text-muted">{{ $gadget->employee->department }}</div>
                            </td>
                            <td>{{ $gadget->device_type }} {{ $gadget->make_model }}</td>
                            <td>{{ $gadget->colour }}</td>
                            <td class="font-monospace">{{ $gadget->seal_number ?: '-' }}</td>
                            <td class="font-monospace small">{{ $gadget->sticker_code }}</td>
                            <td class="small">
                                {{ $gadget->created_at->format('d M Y, H:i') }}<br>
                                <span class="text-muted">{{ $gadget->registrar?->name }}</span>
                            </td>
                            <td>{{ $v?->times ?? 0 }}</td>
                            <td class="small">{{ $v ? \Illuminate\Support\Carbon::parse($v->last_in)->format('d M Y, H:i') : '-' }}</td>
                            <td>
                                @if ($inside->has($gadget->id))
                                    <span class="badge bg-success">Inside</span>
                                @else
                                    <span class="badge bg-secondary">Out</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection