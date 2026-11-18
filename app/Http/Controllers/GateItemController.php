<?php

namespace App\Http\Controllers;

use App\Models\VisitorVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GateItemController extends Controller
{
    // The ids of the Gate officers who took in a visit's belongings or arms.
    private function handlerIds(VisitorVisit $visit)
    {
        return $visit->leftItems->pluck('handled_by')
            ->merge([$visit->security?->handled_by])
            ->filter()
            ->unique();
    }

    // "Items to verify": visits whose belongings or arms nobody has approved yet.
    public function index()
    {
        $visits = VisitorVisit::query()
            ->where(function ($q) {
                $q->whereHas('leftItems', fn ($i) => $i->whereNull('approved_at'))
                    ->orWhereHas('arms', fn ($i) => $i->whereNull('approved_at'));
            })
            ->with(['leftItems.handler', 'arms', 'security.handler', 'arrival'])
            ->orderByDesc('gate_checked_in_at')
            ->get();

        $recent = VisitorVisit::query()
            ->where(function ($q) {
                $q->whereHas('leftItems', fn ($i) => $i->whereNotNull('approved_at'))
                    ->orWhereHas('arms', fn ($i) => $i->whereNotNull('approved_at'));
            })
            ->whereDoesntHave('leftItems', fn ($i) => $i->whereNull('approved_at'))
            ->whereDoesntHave('arms', fn ($i) => $i->whereNull('approved_at'))
            ->with(['leftItems.approver', 'arms.approver'])
            ->orderByDesc('gate_checked_in_at')
            ->limit(15)
            ->get();

        return view('gate.items.index', compact('visits', 'recent'));
    }

    // Approve everything left by one visitor. Never blocks check-in or check-out.
    public function approve(Request $request, VisitorVisit $visit)
    {
        $visit->load(['leftItems', 'security']);

        if ($this->handlerIds($visit)->contains($request->user()->id)) {
            return back()->with('error', 'You took these items in yourself, so someone else must approve them.');
        }

        $pending = $visit->leftItems()->whereNull('approved_at')->count()
            + $visit->arms()->whereNull('approved_at')->count();

        if ($pending === 0) {
            return back()->with('error', "Nothing is waiting for approval for {$visit->name}.");
        }

        // Approving after the visitor has left is allowed, but it is marked as late.
        $afterExit = $visit->gate_checked_out_at !== null;

        $fields = [
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'approved_after_exit' => $afterExit,
        ];

        DB::transaction(function () use ($visit, $fields) {
            $visit->leftItems()->whereNull('approved_at')->update($fields);
            $visit->arms()->whereNull('approved_at')->update($fields);
        });

        return back()->with('success', $afterExit
            ? "Approved for {$visit->name}. Recorded as approved after the visitor left."
            : "Approved for {$visit->name}.");
    }

    // The accountability report.
    public function report(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'status' => 'nullable|in:all,unapproved,late,notreturned',
        ]);

        $from = Carbon::parse($request->input('from', now()->subDays(30)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();
        $status = $request->input('status', 'all');

        $visits = VisitorVisit::query()
            ->whereBetween('gate_checked_in_at', [$from, $to])
            ->where(function ($q) {
                $q->whereHas('leftItems')->orWhereHas('arms');
            })
            ->with(['leftItems.handler', 'leftItems.approver', 'arms.approver', 'arms.returner', 'security.handler'])
            ->orderByDesc('gate_checked_in_at')
            ->get();

        // Work out each visit's state once, then count.
        $visits->each(function ($visit) {
            $rows = $visit->leftItems->concat($visit->arms);
            $visit->rows = $rows;
            $visit->isArmed = $visit->arms->isNotEmpty();
            $visit->allApproved = $rows->every(fn ($row) => $row->approved_at !== null);
            $visit->anyLate = $rows->contains(fn ($row) => (bool) $row->approved_after_exit);
            $visit->stillHeld = $visit->gate_checked_out_at === null;
            $visit->notReturned = $rows->contains(fn ($row) => $row->returned_at === null && $row->not_returned_reason);
            $visit->allReturned = ! $visit->stillHeld && $rows->every(fn ($row) => $row->returned_at !== null);
        });

        $summary = [
            'armed' => $visits->where('isArmed', true)->count(),
            'armed_collected' => $visits->where('isArmed', true)->where('allReturned', true)->count(),
            'armed_held' => $visits->where('isArmed', true)->where('stillHeld', true)->count(),
            'not_returned' => $visits->where('notReturned', true)->count(),
            'unapproved' => $visits->where('allApproved', false)->count(),
            'late' => $visits->where('anyLate', true)->count(),
            'all' => $visits->count(),
        ];

        $shown = match ($status) {
            'unapproved' => $visits->where('allApproved', false),
            'late' => $visits->where('anyLate', true),
            'notreturned' => $visits->where('notReturned', true),
            default => $visits,
        };

        return view('gate.items.report', [
            'visits' => $shown,
            'summary' => $summary,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'status' => $status,
        ]);
    }
}