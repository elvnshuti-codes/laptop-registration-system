@php
    $left = $visit->exceptions->where('type', 'left_unserved')->last();
    $skipped = $visit->exceptions->where('type', 'skipped_entry')->last();
    $sentBack = $visit->exceptions->where('type', 'sent_back');
@endphp

@if ($left || $skipped)
    <div class="alert alert-danger">
        <h6 class="fw-bold mb-2">Security note</h6>

        @if ($left)
            <p class="mb-2">
                <strong>Not served by {{ $left->area }} Reception.</strong>
                Reason given at Entry: <strong>{{ $left->reason }}</strong>.
            </p>
            @if ($left->note)
                <p class="mb-2">Entry note: {{ $left->note }}</p>
            @endif
            <p class="mb-2 small">
                Phone check with Reception:
                {{ $left->phone_confirmed ? 'done' : 'not done' }}@if ($left->confirmed_with), spoke to {{ $left->confirmed_with }}@endif.
                Recorded by {{ $left->recordedBy->name ?? 'Entry Officer' }} at {{ $left->created_at->format('H:i') }}.
            </p>
        @endif

        @if ($skipped)
            <p class="mb-2">
                <strong>Did not go through Entry at check-in.</strong>
                Recorded at {{ $skipped->created_at->format('H:i') }}. Find out where this visitor went on the premises.
            </p>
        @endif

        @if ($sentBack->isNotEmpty())
            <p class="mb-0 small">
                Sent back to Reception {{ $sentBack->count() }} time(s), last at {{ $sentBack->last()->created_at->format('H:i') }}.
            </p>
        @endif
    </div>
@elseif ($sentBack->isNotEmpty())
    <div class="alert alert-warning">
        Sent back to Reception {{ $sentBack->count() }} time(s), last at {{ $sentBack->last()->created_at->format('H:i') }}, and then served.
    </div>
@endif
