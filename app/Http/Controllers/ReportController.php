<?php

namespace App\Http\Controllers;

use App\Models\UnservedReason;
use App\Models\User;
use App\Models\VisitorVisit;
use App\Models\VisitPurpose;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    private const PRESETS = [
        'today' => 'Today',
        'week' => 'This week',
        'month' => 'This month',
        'quarter' => 'This quarter',
        'custom' => 'Custom dates',
    ];

    private const GROUPS = ['day' => 'Day', 'week' => 'Week', 'month' => 'Month'];

    private const WEEKDAYS = [
        1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
        5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
    ];

    // --- Pages ------------------------------------------------------------

    public function index(Request $request)
    {
        $f = $this->filters($request);
        $data = $this->build($f);

        return view('reports.index', $data + [
            'f' => $f,
            'presets' => self::PRESETS,
            'groups' => self::GROUPS,
            'allowedAreas' => $this->allowedAreas(),
            'purposes' => VisitPurpose::orderBy('name')->pluck('name')
                ->merge(VisitorVisit::query()->whereNotNull('purpose')->distinct()->pluck('purpose'))
                ->unique()->sort()->values(),
            'reasonOptions' => UnservedReason::orderBy('name')->pluck('name'),
            'isAdmin' => auth()->user()->hasRole('Administrator'),
            'csvQuery' => array_filter([
                'preset' => $f['preset'],
                'from' => $f['from']->format('Y-m-d'),
                'to' => $f['to']->format('Y-m-d'),
                'group' => $f['group'],
                'area' => $f['area'],
                'purpose' => $f['purpose'],
                'reason' => $f['reason'],
            ]),
        ]);
    }

    // CSV download: ?type=visits (one line per visitor) or ?type=summary (the period table).
    public function csv(Request $request)
    {
        $f = $this->filters($request);
        $data = $this->build($f);
        $type = $request->input('type') === 'summary' ? 'summary' : 'visits';
        $filename = "unbs-visitors-{$type}-{$f['from']->format('Ymd')}-{$f['to']->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($type, $data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // lets Excel read UTF-8 names correctly

            if ($type === 'summary') {
                fputcsv($out, ['Period', 'Visitors', 'Served', 'Left unserved', 'Still waiting', 'Service rate %']);

                foreach ($data['periodRows'] as $p) {
                    fputcsv($out, array_map(fn ($v) => $this->safe($v), [
                        $p['label'], $p['visitors'], $p['served'], $p['left_unserved'], $p['waiting'], $p['rate'] ?? '',
                    ]));
                }
            } else {
                fputcsv($out, [
                    'Visit code', 'Checked in', 'Checked out', 'Name', 'Purpose', 'Area',
                    'Status', 'Served at', 'Unserved reason', 'Note', 'Spoke to', 'Acknowledged at',
                ]);

                foreach ($data['rows'] as $r) {
                    $v = $r['visit'];
                    $u = $r['unserved'];

                    fputcsv($out, array_map(fn ($value) => $this->safe($value), [
                        $v->visit_code,
                        $r['checkIn']->format('Y-m-d H:i'),
                        $v->gate_checked_out_at ? Carbon::parse($v->gate_checked_out_at)->format('Y-m-d H:i') : '',
                        $v->name,
                        $r['purpose'],
                        $r['area'],
                        $r['status'],
                        $r['servedAt'] ? $r['servedAt']->format('Y-m-d H:i') : '',
                        $u?->reason ?? '',
                        $u?->note ?? '',
                        $u?->confirmed_with ?? '',
                        $u?->acknowledged_at ? Carbon::parse($u->acknowledged_at)->format('Y-m-d H:i') : '',
                    ]));
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // --- Filters ----------------------------------------------------------

    // Which areas may this user see? Administrators pass every permission check, so they see both.
    private function allowedAreas(): array
    {
        $user = auth()->user();
        $areas = [];

        if ($user->can('manage visitor reception')) {
            $areas[] = 'standard';
        }
        if ($user->can('manage sample reception')) {
            $areas[] = 'sample';
        }

        abort_if(empty($areas), 403, 'Your account is not linked to a reception area.');

        return $areas;
    }

    private function filters(Request $request): array
    {
        $request->validate([
            'preset' => ['nullable', Rule::in(array_keys(self::PRESETS))],
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'group' => ['nullable', Rule::in(array_keys(self::GROUPS))],
            'area' => ['nullable', Rule::in(['all', 'standard', 'sample'])],
            'purpose' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:100',
        ]);

        $today = today();
        $preset = $request->input('preset') ?: 'week';

        $customFrom = $request->filled('from') ? Carbon::parse($request->input('from')) : $today->copy()->startOfMonth();
        $customTo = $request->filled('to') ? Carbon::parse($request->input('to')) : $today->copy();

        [$from, $to] = match ($preset) {
            'today' => [$today->copy(), $today->copy()],
            'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'quarter' => [$today->copy()->startOfQuarter(), $today->copy()->endOfQuarter()],
            'custom' => [$customFrom, $customTo],
            default => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
        };

        // Choose a sensible grouping unless the user picked one.
        $days = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $group = $request->input('group') ?: ($days <= 31 ? 'day' : ($days <= 120 ? 'week' : 'month'));

        $allowed = $this->allowedAreas();
        $requested = $request->input('area', 'all');
        $area = in_array($requested, $allowed, true) ? $requested : 'all';

        return [
            'preset' => $preset,
            'from' => $from,
            'to' => $to,
            'group' => $group,
            'area' => $area,
            'areas' => $area === 'all' ? $allowed : [$area],
            'purpose' => $request->input('purpose') ?: null,
            'reason' => $request->input('reason') ?: null,
        ];
    }

    // --- Numbers ----------------------------------------------------------

    private function build(array $f): array
    {
        $start = $f['from']->copy()->startOfDay();
        $end = $f['to']->copy()->endOfDay();

        $visits = VisitorVisit::query()
            ->whereNotNull('gate_checked_in_at')
            ->whereBetween('gate_checked_in_at', [$start, $end])
            ->when(count($f['areas']) === 1, function ($q) use ($f) {
                if ($f['areas'][0] === 'sample') {
                    $q->where('for_sample_reception', true);
                } else {
                    $q->where(fn ($w) => $w->where('for_sample_reception', false)->orWhereNull('for_sample_reception'));
                }
            })
            ->when($f['purpose'], fn ($q, $purpose) => $q->where('purpose', $purpose))
            ->with(['receptions', 'sampleReception', 'exceptions'])
            ->orderBy('gate_checked_in_at')
            ->get();

        // One tidy row per visitor, worked out from the records already loaded.
        $rows = $visits->map(function (VisitorVisit $v) {
            $isSample = (bool) $v->for_sample_reception;

            if ($isSample) {
                $served = $v->sampleReception !== null;
                $servedAt = $v->sampleReception?->served_at;
                $handledBy = $v->sampleReception?->served_by;
            } else {
                $first = $v->receptions->filter(fn ($r) => (bool) $r->served)->sortBy('served_at')->first();
                $served = $first !== null;
                $servedAt = $first?->served_at;
                $handledBy = $first?->received_by;
            }

            $unserved = $v->exceptions->where('type', 'left_unserved')->sortByDesc('created_at')->first();
            $left = $v->gate_checked_out_at !== null;

            $status = match (true) {
                $served => 'Served',
                $unserved !== null => 'Left unserved',
                $left => 'Left, not served',
                default => 'Waiting',
            };

            return [
                'visit' => $v,
                'checkIn' => Carbon::parse($v->gate_checked_in_at),
                'area' => $isSample ? 'Sample House' : 'Standard House',
                'purpose' => $v->purpose,
                'served' => $served,
                'servedAt' => $servedAt ? Carbon::parse($servedAt) : null,
                'handledBy' => $handledBy,
                'status' => $status,
                'unserved' => $unserved,
                'sentBack' => $v->exceptions->where('type', 'sent_back')->count(),
                'skipped' => $v->exceptions->where('type', 'skipped_entry')->count(),
            ];
        });

        // Headline numbers, including returning visitors (same ID seen before, or more than once now).
        $summary = $this->summaryRow('Total', $rows);

        $idCounts = $visits->pluck('id_document_number')->filter()->countBy();
        $seenBefore = VisitorVisit::whereIn('id_document_number', $idCounts->keys())
            ->where('gate_checked_in_at', '<', $start)
            ->distinct()
            ->pluck('id_document_number')
            ->flip();

        $summary['distinct'] = $idCounts->count();
        $summary['returning'] = $idCounts->filter(fn ($n, $id) => $n > 1 || $seenBefore->has($id))->count();
        $summary['other'] = $rows->where('status', 'Left, not served')->count();

        // Visitors over time.
        $grouped = $rows->groupBy(fn ($r) => $this->periodKey($r['checkIn'], $f['group']));
        $periodRows = [];
        foreach ($this->periods($f) as $key => $label) {
            $periodRows[] = $this->summaryRow($label, $grouped->get($key, collect()));
        }

        // By purpose.
        $purposeRows = $rows->groupBy('purpose')
            ->map(fn ($g, $purpose) => $this->summaryRow((string) $purpose, $g))
            ->sortByDesc('visitors')
            ->values();

        // Busiest hours and weekdays.
        $hours = $rows->countBy(fn ($r) => $r['checkIn']->format('H') . ':00')->sortKeys();
        $byDay = $rows->countBy(fn ($r) => $r['checkIn']->dayOfWeekIso);
        $weekdays = collect(self::WEEKDAYS)
            ->map(fn ($name, $n) => ['name' => $name, 'count' => $byDay->get($n, 0)])
            ->values();

        // Visitors who left without being served (the reason filter applies here only).
        $records = $rows->filter(fn ($r) => $r['unserved'] && (! $f['reason'] || $r['unserved']->reason === $f['reason']));
        $acked = $records->filter(fn ($r) => $r['unserved']->acknowledged_at);

        $unserved = [
            'total' => $records->count(),
            'byReason' => $records->countBy(fn ($r) => $r['unserved']->reason)->sortDesc(),
            'byArea' => $records->countBy(fn ($r) => $r['area']),
            'acknowledged' => $acked->count(),
            'pending' => $records->count() - $acked->count(),
            'avgAckMinutes' => $acked->isEmpty() ? null : round($acked->avg(
                fn ($r) => abs(Carbon::parse($r['unserved']->created_at)->diffInMinutes(Carbon::parse($r['unserved']->acknowledged_at)))
            )),
            'sentBackEvents' => $rows->sum('sentBack'),
            'sentBackVisitors' => $rows->where('sentBack', '>', 0)->count(),
            'skipped' => $rows->sum('skipped'),
        ];

        // Workload per receptionist: administrator only.
        $handled = collect();
        if (auth()->user()->hasRole('Administrator')) {
            $counts = $rows->where('served', true)
                ->filter(fn ($r) => $r['handledBy'])
                ->countBy(fn ($r) => $r['handledBy']);
            $names = User::whereIn('id', $counts->keys())->pluck('name', 'id');

            $handled = $counts
                ->map(fn ($n, $id) => ['name' => $names[$id] ?? "User #{$id}", 'count' => $n])
                ->sortByDesc('count')
                ->values();
        }

        return compact('rows', 'summary', 'periodRows', 'purposeRows', 'hours', 'weekdays', 'unserved', 'handled');
    }

    // Counts for any group of visitor rows.
    private function summaryRow(string $label, $group): array
    {
        $total = $group->count();
        $served = $group->where('status', 'Served')->count();
        $leftUnserved = $group->where('status', 'Left unserved')->count();
        $waiting = $group->where('status', 'Waiting')->count();
        $base = $total - $waiting;

        return [
            'label' => $label,
            'visitors' => $total,
            'served' => $served,
            'left_unserved' => $leftUnserved,
            'waiting' => $waiting,
            'rate' => $base > 0 ? round($served / $base * 100) : null,
        ];
    }

    // Every period in the range (up to today), so empty days still show as zero.
    private function periods(array $f): array
    {
        $cap = $f['to']->copy()->startOfDay();
        if ($cap->gt(today())) {
            $cap = today();
        }

        $periods = [];
        for ($d = $f['from']->copy()->startOfDay(); $d->lte($cap); $d->addDay()) {
            $periods[$this->periodKey($d, $f['group'])] ??= $this->periodLabel($d, $f['group']);
        }

        return $periods;
    }

    private function periodKey(Carbon $date, string $group): string
    {
        return match ($group) {
            'week' => $date->copy()->startOfWeek()->format('Y-m-d'),
            'month' => $date->format('Y-m'),
            default => $date->format('Y-m-d'),
        };
    }

    private function periodLabel(Carbon $date, string $group): string
    {
        return match ($group) {
            'week' => 'Week of ' . $date->copy()->startOfWeek()->format('d M Y'),
            'month' => $date->format('F Y'),
            default => $date->format('D d M Y'),
        };
    }

    // Stops a name like "=cmd" from being run as a formula when the CSV is opened in Excel.
    private function safe($value): string
    {
        $text = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $text) ? "'" . $text : $text;
    }
}
