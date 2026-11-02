<?php

namespace App\Http\Controllers;

use App\Models\VisitorReception;
use App\Models\VisitorVisit;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    // Finds a visitor still on site from a scanned or typed visit code.
    // Barcodes carry hyphens, printed codes carry slashes: treat them the same.
    private function findOnSiteVisit(string $code): ?VisitorVisit
    {
        $code = str_replace('/', '-', trim($code));

        return VisitorVisit::whereNull('gate_checked_out_at')
            ->whereRaw("REPLACE(visit_code, '/', '-') = ?", [$code])
            ->first();
    }

    // Everyone currently on site, with device and reception status.
    public function index()
    {
        $visits = VisitorVisit::whereNotNull('gate_checked_in_at')
            ->whereNull('gate_checked_out_at')
            ->withCount([
                'devices as devices_on_site' => fn ($q) => $q->whereNull('device_checked_out_at'),
                'receptions as receptions_total',
                'receptions as receptions_served' => fn ($q) => $q->where('served', true),
            ])
            ->orderBy('gate_checked_in_at')
            ->get();

        return view('reception.index', compact('visits'));
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

    // Marks one reception record as served.
    public function markServed(VisitorReception $reception)
    {
        if ($reception->served) {
            return back()->with('error', 'That visit is already marked as served.');
        }

        $reception->update(['served' => true]);

        return back()->with('success', 'Marked as served.');
    }
}