<?php

namespace App\Http\Controllers;

use App\Models\VisitException;
use App\Models\VisitorReception;
use App\Models\VisitorVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    // Minutes a visitor may wait before the wait badge turns amber / red.
    private const WAIT_WARN = 30;
    private const WAIT_LATE = 60;

    // Finds a visitor still on site from a scanned or typed visit code.
    // Barcodes carry hyphens, printed codes carry slashes: treat them the same.
    private function findOnSiteVisit(string $code): ?VisitorVisit
    {
        $code = str_replace('/', '-', trim($code));

        return VisitorVisit::whereNull('gate_checked_out_at')
            ->whereRaw("REPLACE(visit_code, '/', '-') = ?", [$code])
            ->first();
    }

    // Strict flow: a visitor must go Gate -> Entry -> Reception.
    // "Passed Entry" means a device was registered, or the Entry officer
    // confirmed the visitor has no device.
    private function passedEntry(VisitorVisit $visit): bool
    {
        return $visit->no_device_confirmed_at !== null || $visit->devices()->exists();
    }

    private function notPassedEntryMessage(VisitorVisit $visit): string
    {
        return "{$visit->name} has not been through Entry yet. Send the visitor to the Entry checkpoint first.";
    }

    // Testing visitors are served at Sample Reception, not here.
    private function wrongReception(VisitorVisit $visit): ?string
    {
        return $visit->for_sample_reception
            ? "{$visit->name} is for Sample Reception, not Standard House Reception."
            : null;
    }

    // Standard House visitors currently on site, each with a stage and a wait time.
    public function index()
    {
        $visits = VisitorVisit::whereNotNull('gate_checked_in_at')
            ->whereNull('gate_checked_out_at')
            ->where(fn ($q) => $q->where('for_sample_reception', false)->orWhereNull('for_sample_reception'))
            ->with(['devices', 'receptions'])
            ->orderBy('gate_checked_in_at')
            ->get();

        $rows = $visits->map(function (VisitorVisit $v) {
            $passedEntry = $v->no_device_confirmed_at !== null || $v->devices->isNotEmpty();
            $received = $v->receptions->isNotEmpty();
            $allServed = $received && $v->receptions->every(fn ($r) => (bool) $r->served);
            $checkIn = Carbon::parse($v->gate_checked_in_at);
            $minutes = (int) abs(now()->diffInMinutes($checkIn));
            $firstServed = $v->receptions->filter(fn ($r) => (bool) $r->served)->sortBy('served_at')->first();

            return [
                'visit' => $v,
                'stage' => ! $passedEntry ? 'entry' : ($allServed ? 'served' : ($received ? 'progress' : 'waiting')),
                'checkIn' => $checkIn,
                'sameDay' => $checkIn->isToday(),
                'wait' => $minutes < 60 ? "{$minutes} min" : intdiv($minutes, 60) . ' h ' . ($minutes % 60) . ' min',
                'warn' => $minutes >= self::WAIT_WARN,
                'late' => $minutes >= self::WAIT_LATE,
                'servedAt' => $firstServed?->served_at ? Carbon::parse($firstServed->served_at) : null,
                'destination' => $v->receptions->pluck('destination')->filter()->unique()->implode(', '),
            ];
        });

        // Standard House visitors Entry recorded as leaving unserved, not yet acknowledged.
        // These stay listed even after the visitor has left, until someone acknowledges them.
        $unserved = VisitException::where('type', 'left_unserved')
            ->whereNull('acknowledged_at')
            ->whereHas('visit', fn ($q) => $q->where('for_sample_reception', false))
            ->with(['visit', 'recordedBy'])
            ->latest()
            ->get();

        return view('reception.index', compact('rows', 'unserved'));
    }

    // Scan or type a visit code to jump to that visitor.
    public function lookup(Request $request)
    {
        $request->validate(['code' => 'required|string|max:100']);

        $visit = $this->findOnSiteVisit($request->input('code'));

        if (! $visit) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'No visitor on site has that code. Check it and try again.']);
        }

        if ($message = $this->wrongReception($visit)) {
            return back()->withInput()->withErrors(['code' => $message]);
        }

        if (! $this->passedEntry($visit)) {
            return back()
                ->withInput()
                ->withErrors(['code' => $this->notPassedEntryMessage($visit)]);
        }

        return redirect()->route('reception.visits.show', $visit);
    }

    // One visitor: details, reception records so far, and the form to add one.
    public function show(VisitorVisit $visit)
    {
        if ($visit->gate_checked_out_at) {
            return redirect()
                ->route('reception.index')
                ->with('error', "{$visit->name} has already left the premises.");
        }

        if ($message = $this->wrongReception($visit)) {
            return redirect()->route('reception.index')->with('error', $message);
        }

        if (! $this->passedEntry($visit)) {
            return redirect()
                ->route('reception.index')
                ->with('error', $this->notPassedEntryMessage($visit));
        }

        $visit->load(['devices', 'receptions.receivedBy']);

        return view('reception.show', compact('visit'));
    }

    // Records that this receptionist received the visitor at a destination.
    public function store(Request $request, VisitorVisit $visit)
    {
        if ($visit->gate_checked_out_at) {
            return redirect()
                ->route('reception.index')
                ->with('error', "{$visit->name} has already left the premises.");
        }

        if ($message = $this->wrongReception($visit)) {
            return redirect()->route('reception.index')->with('error', $message);
        }

        if (! $this->passedEntry($visit)) {
            return redirect()
                ->route('reception.index')
                ->with('error', $this->notPassedEntryMessage($visit));
        }

        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'meeting_with' => 'nullable|string|max:255',
            'purpose_confirmed' => 'required|string|max:255',
        ]);

        VisitorReception::create([
            ...$validated,
            'visitor_visit_id' => $visit->id,
            'served' => false,
            'received_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('reception.visits.show', $visit)
            ->with('success', "{$visit->name} received.");
    }

    // Marks one reception record as served, with an optional note and the time.
    public function markServed(Request $request, VisitorReception $reception)
    {
        if ($reception->served) {
            return back()->with('error', 'That visit is already marked as served.');
        }

        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $reception->update([
            'served' => true,
            'served_at' => now(),
            'note' => $validated['note'] ?? null,
        ]);

        return back()->with('success', 'Marked as served.');
    }

    // The receptionist confirms they have seen a "left without being served" record.
    public function acknowledge(Request $request, VisitException $exception)
    {
        abort_unless($exception->type === 'left_unserved', 404);
        abort_if($exception->visit->for_sample_reception, 403, 'This record belongs to Sample Reception.');

        if ($exception->acknowledged_at) {
            return back()->with('error', 'That record was already acknowledged.');
        }

        $exception->update([
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now(),
        ]);

        return back()->with('success', "Acknowledged: {$exception->visit->name}.");
    }
}