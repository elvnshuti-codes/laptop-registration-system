<?php

namespace App\Http\Controllers;

use App\Models\SampleReception;
use App\Models\VisitException;
use App\Models\VisitorVisit;
use Illuminate\Http\Request;

class SampleReceptionController extends Controller
{
    // Dashboard: every Testing visitor still on site, waiting ones first.
    public function index()
    {
        $visits = VisitorVisit::where('for_sample_reception', true)
            ->whereNotNull('gate_checked_in_at')
            ->whereNull('gate_checked_out_at')
            ->with(['samples', 'sampleReception', 'devices', 'receptions', 'exceptions'])
            ->withCount(['devices as devices_on_site' => fn ($q) => $q->whereNull('device_checked_out_at')])
            ->orderBy('gate_checked_in_at')
            ->get()
            ->sortBy(fn ($visit) => $visit->sampleReception ? 1 : 0)
            ->values();

        $onSite = $visits->count();
        $served = $visits->filter(fn ($visit) => $visit->sampleReception)->count();
        $waiting = $onSite - $served;

        // Testing visitors Entry recorded as leaving unserved, not yet acknowledged.
        // These stay listed even after the visitor has left, until someone acknowledges them.
        $unserved = VisitException::where('type', 'left_unserved')
            ->whereNull('acknowledged_at')
            ->whereHas('visit', fn ($q) => $q->where('for_sample_reception', true))
            ->with(['visit', 'recordedBy'])
            ->latest()
            ->get();

        return view('sample-reception.index', compact('visits', 'onSite', 'served', 'waiting', 'unserved'));
    }

    // Scan or type a visit code to open that visitor's page.
    public function lookup(Request $request)
    {
        $request->validate(['code' => 'required|string|max:100']);

        // Barcodes carry hyphens, printed codes carry slashes: treat them the same.
        $code = str_replace('/', '-', trim($request->input('code')));

        $visit = VisitorVisit::whereNull('gate_checked_out_at')
            ->whereRaw("REPLACE(visit_code, '/', '-') = ?", [$code])
            ->first();

        if (! $visit) {
            return back()->withInput()->withErrors(['code' => 'No visitor on site has that code. Check it and try again.']);
        }

        if (! $visit->for_sample_reception) {
            return back()->withInput()->withErrors(['code' => 'This visitor is for Standard House Reception, not Sample Reception.']);
        }

        return redirect()->route('sample.visits.show', $visit);
    }

    // The visitor's page: details, samples, devices, and the serve form.
    public function show(VisitorVisit $visit)
    {
        abort_unless($visit->for_sample_reception, 403, 'This visitor is not for Sample Reception.');

        $visit->load(['samples', 'devices', 'sampleReception.servedBy']);

        return view('sample-reception.show', compact('visit'));
    }

    // Marks the visitor as served, with the reference number and an optional note.
    public function serve(Request $request, VisitorVisit $visit)
    {
        abort_unless($visit->for_sample_reception, 403, 'This visitor is not for Sample Reception.');

        if ($visit->gate_checked_out_at) {
            return back()->with('error', "{$visit->name} has already left the premises.");
        }

        if ($visit->sampleReception()->exists()) {
            return back()->with('error', "{$visit->name} has already been served.");
        }

        $request->merge(['reference_number' => strtoupper(trim((string) $request->input('reference_number')))]);

        $validated = $request->validate([
            'reference_number' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:500',
        ]);

        SampleReception::create([
            'visitor_visit_id' => $visit->id,
            'served_by' => auth()->id(),
            'reference_number' => $validated['reference_number'] ?: null,
            'note' => $validated['note'] ?? null,
            'served_at' => now(),
        ]);

        return redirect()
            ->route('sample.visits.show', $visit)
            ->with('success', "{$visit->name} marked as served.");
    }

    // The sample officer confirms they have seen a "left without being served" record.
    public function acknowledge(Request $request, VisitException $exception)
    {
        abort_unless($exception->type === 'left_unserved', 404);
        abort_unless($exception->visit->for_sample_reception, 403, 'This record belongs to Standard House Reception.');

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