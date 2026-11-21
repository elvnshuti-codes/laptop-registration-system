<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VisitorVisit;
use App\Models\VisitPurpose;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VisitorRecordController extends Controller
{
    private const STATUSES = [
        'all' => 'All visitors',
        'served' => 'Served',
        'unserved' => 'Left without being served',
        'followup' => 'Left unserved, not yet acknowledged',
        'onsite' => 'Still on site',
    ];

    private const EXCEPTION_LABELS = [
        'sent_back' => 'Sent back to Reception',
        'left_unserved' => 'Left without being served',
        'skipped_entry' => 'Skipped Entry at check-in',
    ];

    private const FILTER_KEYS = ['q', 'from', 'to', 'status', 'area', 'purpose'];

    // The areas a person can be allowed to see, and the site value stored on each visit.
    private const AREA_SITES = ['standard' => 'standard_house', 'sample' => 'sample_house', 'rig' => 'rig'];
    private const SITE_LABELS = ['standard_house' => 'Standard House', 'sample_house' => 'Sample House', 'rig' => 'Rig'];

    // --- Pages ------------------------------------------------------------

    public function index(Request $request)
    {
        [$query, $allowed] = $this->search($request);

        $total = (clone $query)->count();
        $visits = $query
            ->with(['devices', 'receptions', 'sampleReception', 'exceptions'])
            ->orderByDesc('gate_checked_in_at')
            ->limit(200)
            ->get();

        return view('records.index', [
            'rows' => $visits->map(fn ($v) => $this->describe($v)),
            'total' => $total,
            'statuses' => self::STATUSES,
            'allowedAreas' => $allowed,
            'purposes' => VisitPurpose::orderBy('name')->pluck('name')
                ->merge(VisitorVisit::query()->whereNotNull('purpose')->distinct()->pluck('purpose'))
                ->unique()->sort()->values(),
            'csvQuery' => array_filter($request->only(self::FILTER_KEYS)),
        ]);
    }

    // One visitor: details and the full timeline.
    public function show(VisitorVisit $visit)
    {
        $area = array_search($visit->site, self::AREA_SITES, true) ?: ($visit->for_sample_reception ? 'sample' : 'standard');
        abort_unless(in_array($area, $this->allowedAreas(), true), 403, 'This visitor belongs to another area.');

        $visit->load([
            'devices', 'samples', 'receptions.receivedBy', 'sampleReception.servedBy',
            'exceptions.recordedBy', 'exceptions.acknowledgedBy',
        ]);

        return view('records.show', [
            'visit' => $visit,
            'info' => $this->describe($visit),
            'clearedBy' => $visit->entry_cleared_by ? User::find($visit->entry_cleared_by)?->name : null,
            'exceptionLabels' => self::EXCEPTION_LABELS,
        ]);
    }

    // The current search as a CSV (latest 5000 matches). ID numbers are left out on purpose.
    public function csv(Request $request)
    {
        [$query] = $this->search($request);

        $rows = $query
            ->with(['devices', 'receptions', 'sampleReception', 'exceptions'])
            ->orderByDesc('gate_checked_in_at')
            ->limit(5000)
            ->get()
            ->map(fn ($v) => $this->describe($v));

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Visit code', 'Name', 'Mobile', 'Purpose', 'Area', 'Checked in', 'Entry','Received at Reception',
                'Served at', 'Cleared at Entry', 'Checked out', 'Time on premises', 'Destination', 'Meeting with',
                'Status', 'Unserved reason', 'Note', 'Spoke to', 'Acknowledged at',
            ]);

            $fmt = fn ($d) => $d ? $d->format('Y-m-d H:i') : '';

            foreach ($rows as $r) {
                $v = $r['visit'];
                $u = $r['unserved'];

                fputcsv($out, array_map(fn ($value) => $this->safe($value), [
                    $v->visit_code, $v->name, $v->visitor_mobile_number, $r['purpose'], $r['area'],
                    $fmt($r['checkIn']), $fmt($r['entryAt']), $fmt($r['receivedAt']),
                    $fmt($r['servedAt']), $fmt($r['clearedAt']), $fmt($r['checkOut']),
                    $r['duration'], $r['destinations'], $r['meeting'],
                    $r['status'], $u?->reason ?? '', $u?->note ?? '', $u?->confirmed_with?? '',
                    $u?->acknowledged_at ? Carbon::parse($u->acknowledged_at)->format('Y-m-d H:i') : '',
                ]));
            }

            fclose($out);
        }, 'unbs-visitor-records-' . now()->format('Ymd-Hi') . '.csv', ['Content-Type' =>'text/csv; charset=UTF-8']);
    }

    // --- Search -----------------------------------------------------------

    // Which areas may this user see? Administrators pass every permission check, so theysee both.
    private function allowedAreas(): array
    {
        $user = auth()->user();
        $areas = [];

        if ($user->can('view standard house data')) {
            $areas[] = 'standard';
        }
        if ($user->can('view sample house data')) {
            $areas[] = 'sample';
        }
        if ($user->can('view rig data')) {
            $areas[] = 'rig';
        }

        abort_if(empty($areas), 403, 'Your account is not linked to a reception area.');

        return $areas;
    }

    // The site values that match a list of areas.
    private function sites(array $areas): array
    {
        return array_values(array_map(fn ($a) => self::AREA_SITES[$a], $areas));
    }

    // Builds the visitor query from the search form. Returns [query, allowed areas].
    private function search(Request $request): array
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'area' => ['nullable', Rule::in(['all', 'standard', 'sample', 'rig'])],
            'purpose' => 'nullable|string|max:255',
        ]);

        $allowed = $this->allowedAreas();
        $requested = $request->input('area', 'all');
        $areas = in_array($requested, $allowed, true) ? [$requested] : $allowed;

        $query = VisitorVisit::query()
            ->whereNotNull('gate_checked_in_at')
            ->whereIn('site', $this->sites($areas))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%' . trim($request->input('q')) . '%';

                $q->where(fn ($w) => $w
                    ->where('name', 'like', $like)
                    ->orWhere('visit_code', 'like', $like)
                    ->orWhere('id_document_number', 'like', $like)
                    ->orWhere('visitor_mobile_number', 'like', $like));
            })
            ->when($request->filled('from'), fn ($q) => $q->where('gate_checked_in_at', '>=', Carbon::parse($request->input('from'))->startOfDay()))
            ->when($request->filled('to'), fn ($q) => $q->where('gate_checked_in_at', '<=', Carbon::parse($request->input('to'))->endOfDay()))
            ->when($request->filled('purpose'), fn ($q) => $q->where('purpose', $request->input('purpose')))
            ->when($request->filled('status'), function ($q) use ($request) {
                match ($request->input('status')) {
                    'served' => $q->where(fn ($w) => $w
                        ->whereHas('receptions', fn ($r) => $r->where('served', true))
                        ->orWhereHas('sampleReception')),
                    'unserved' => $q->whereHas('exceptions', fn ($e) => $e->where('type','left_unserved')),
                    'followup' => $q->whereHas('exceptions', fn ($e) => $e->where('type','left_unserved')->whereNull('acknowledged_at')),
                    'onsite' => $q->whereNull('gate_checked_out_at'),
                    default => null,
                };
            });

        return [$query, $allowed];
    }

    // --- One visitor's story ----------------------------------------------

    // Works out status, key times and durations from the records already loaded.
    private function describe(VisitorVisit $v): array
    {
        $isSample = (bool) $v->for_sample_reception;

        if ($isSample) {
            $served = $v->sampleReception !== null;
            $servedAt = $v->sampleReception?->served_at;
            $receivedAt = null;
        } else {
            $first = $v->receptions->filter(fn ($r) => (bool) $r->served)->sortBy('served_at')->first();
            $served = $first !== null;
            $servedAt = $first?->served_at;
            $receivedAt = $v->receptions->sortBy('created_at')->first()?->created_at;
        }

        $unserved = $v->exceptions->where('type', 'left_unserved')->sortByDesc('created_at')->first();
        $checkIn = Carbon::parse($v->gate_checked_in_at);
        $checkOut = $v->gate_checked_out_at ? Carbon::parse($v->gate_checked_out_at) : null;

        $entryAt = $v->devices->pluck('device_checked_in_at')->filter()->map(fn ($d) => Carbon::parse($d))->sort()->first()
            ?? ($v->no_device_confirmed_at ? Carbon::parse($v->no_device_confirmed_at) : null);

        $minutes = (int) abs(($checkOut ?? now())->diffInMinutes($checkIn));
        $duration = $minutes < 60 ? "{$minutes} min" : intdiv($minutes, 60) . ' h ' . ($minutes % 60) . ' min';

        return [
            'visit' => $v,
            'area' => self::SITE_LABELS[$v->site] ?? ($isSample ? 'Sample House' : 'Standard House'),
            'purpose' => $v->purpose,
            'checkIn' => $checkIn,
            'entryAt' => $entryAt,
            'receivedAt' => $receivedAt ? Carbon::parse($receivedAt) : null,
            'servedAt' => $servedAt ? Carbon::parse($servedAt) : null,
            'clearedAt' => $v->entry_cleared_at ? Carbon::parse($v->entry_cleared_at) : null,
            'checkOut' => $checkOut,
            'duration' => $duration . ($checkOut ? '' : ' (still on site)'),
            'destinations' => $isSample ? 'Sample Reception' : $v->receptions->pluck('destination')->filter()->unique()->implode(', '),
            'meeting' => $v->receptions->pluck('meeting_with')->filter()->unique()->implode(', '),
            'unserved' => $unserved,
            'status' => match (true) {
                $served => 'Served',
                $unserved !== null => 'Left unserved',
                $checkOut !== null => 'Left, not served',
                default => 'Waiting',
            },
        ];
    }

    // Stops a name like "=cmd" from being run as a formula when the CSV is opened in Excel.
    private function safe($value): string
    {
        $text = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $text) ? "'" . $text : $text;
    }
}