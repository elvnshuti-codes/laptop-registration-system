@extends('layouts.app')

@section('title', 'Items to Verify')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Items to verify</h4>
                    <small class="opacity-75">Belongings and arms left at the gate that nobody has approved yet.</small>
                </div>
                @can('view gate items report')
                    <a href="{{ route('gate.items.report') }}" class="btn btn-sm btn-outline-light">Accountability report</a>
                @endcan
            </div>

            <div class="card-body p-0">
                @forelse ($visits as $visit)
                    @php
                        $handlerIds = $visit->leftItems->pluck('handled_by')->merge([$visit->security?->handled_by])->filter()->unique();
                        $mine = $handlerIds->contains(auth()->id());
                        $left = $visit->gate_checked_out_at !== null;
                    @endphp
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">
                                    {{ $visit->name }}
                                    <span class="text-muted fw-normal">&middot; card {{ $visit->visitor_card_number }}</span>
                                    @if ($left)
                                        <span class="badge bg-secondary">Has left</span>
                                    @else
                                        <span class="badge bg-success">On site</span>
                                    @endif
                                </div>
                                <div class="small text-muted font-monospace">{{ $visit->visit_code }}</div>
                                <div class="small text-muted">
                                    In {{ \Carbon\Carbon::parse($visit->gate_checked_in_at)->format('d M Y, H:i') }}
                                    @if ($left) &middot; out {{ \Carbon\Carbon::parse($visit->gate_checked_out_at)->format('d M Y, H:i') }} @endif
                                </div>
                            </div>

                            <div class="text-end">
                                @if ($mine)
                                    <span class="small text-muted">You took these in, so someone else must approve.</span>
                                @else
                                    <form method="POST" action="{{ route('gate.items.approve', $visit) }}"
                                          onsubmit="return confirm('Approve everything recorded for {{ addslashes($visit->name) }}?');">
                                        @csrf
                                        <button type="submit" class="btn btn-success">Approve</button>
                                    </form>
                                    @if ($left)
                                        <div class="small text-danger mt-1">Visitor has left: this will be marked late.</div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        @if ($visit->security)
                            <div class="mt-2 small">
                                <strong>{{ $visit->security->force }} &middot; {{ $visit->security->officer_rank }}</strong>
                                &mdash; protecting {{ $visit->security->vip_name }} ({{ $visit->security->vip_title }})
                                &middot; taken in by {{ $visit->security->handler->name }}
                            </div>
                        @endif

                        <ul class="mt-2 mb-0">
                            @foreach ($visit->arms as $arm)
                                <li>{{ $arm->summary() }}</li>
                            @endforeach
                            @foreach ($visit->leftItems as $item)
                                <li>{{ $item->quantity }} &times; {{ $item->colour }} {{ $item->item_name }}
                                    <span class="small text-muted">(taken in by {{ $item->handler->name }})</span></li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">Nothing is waiting for approval.</div>
                @endforelse
            </div>
        </div>

        @if ($recent->isNotEmpty())
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Recently approved</div>
                <ul class="list-group list-group-flush">
                    @foreach ($recent as $visit)
                        @php $row = $visit->arms->first() ?? $visit->leftItems->first(); @endphp
                        <li class="list-group-item small">
                            {{ $visit->name }} &middot; approved by {{ $row->approver?->name }}
                            on {{ $row->approved_at->format('d M Y, H:i') }}
                            @if ($row->approved_after_exit) <span class="badge bg-warning text-dark">After the visitor left</span> @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div>
</div>
@endsection