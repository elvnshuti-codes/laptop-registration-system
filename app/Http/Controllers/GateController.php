<?php

namespace App\Http\Controllers;

use App\Models\VisitorVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GateController extends Controller
{
    // --- Check-in ---------------------------------------------------------

    // Shows the Gate check-in form.
    public function create()
    {
        return view('gate.create');
    }

    // Handles form submission: validates, generates the ticket number
    // inside a locked transaction, and creates the visit.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'purpose_other' => 'nullable|string|max:255',
            'id_document_type' => 'required|string|max:255',
            'id_document_other' => 'nullable|string|max:255',
            'visitor_card_number' => 'required|string|max:255',
            'visitor_mobile_number' => 'nullable|string|max:20',
        ]);

        $visit = DB::transaction(function () use ($validated) {
            $today = now()->toDateString();

            // Make sure today's counter row exists (the unique ticket_date index
            // makes this safe even if two officers try at the same instant),
            // then lock it so no two visitors can ever receive the same number.
            DB::table('visitor_ticket_sequences')->insertOrIgnore([
                'ticket_date' => $today,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('visitor_ticket_sequences')
                ->where('ticket_date', $today)
                ->lockForUpdate()
                ->first();

            $nextNumber = $sequence->last_number + 1;

            DB::table('visitor_ticket_sequences')
                ->where('id', $sequence->id)
                ->update(['last_number' => $nextNumber, 'updated_at' => now()]);

            $visitCode = sprintf('UNBS/V%03d/%s-%s', $nextNumber, now()->format('D'), strtoupper(now()->format('dMy')));

            return VisitorVisit::create([
                ...$validated,
                'visit_code' => $visitCode,
                'gate_checked_in_at' => now(),
            ]);
        });

        return redirect()
            ->route('gate.ticket', $visit)
            ->with('success', 'Visitor checked in successfully.');
    }

    // Shows the printable ticket after a successful check-in.
    public function ticket(VisitorVisit $visit)
    {
        return view('gate.ticket', compact('visit'));
    }

    // --- Check-out --------------------------------------------------------

    // Finds a visitor still on site from a scanned or typed visit code.
    private function findOnSiteVisit(string $code): ?VisitorVisit
    {
        // Barcodes carry hyphens, printed codes carry slashes: treat them the same.
        $code = str_replace('/', '-', trim($code));

        return VisitorVisit::whereNull('gate_checked_out_at')
            ->whereRaw("REPLACE(visit_code, '/', '-') = ?", [$code])
            ->first();
    }

    // Lists everyone checked in at the gate who has not yet left,
    // with a count of devices still checked in for each visitor.
    public function checkoutIndex()
    {
        $visits = VisitorVisit::whereNotNull('gate_checked_in_at')
            ->whereNull('gate_checked_out_at')
            ->withCount(['devices as devices_on_site' => fn ($q) => $q->whereNull('device_checked_out_at')])
            ->orderBy('gate_checked_in_at')
            ->get();

        return view('gate.checkout', compact('visits'));
    }

    // Scan or type a visit code to jump straight to that visitor's check-out page.
    public function checkoutLookup(Request $request)
    {
        $request->validate(['code' => 'required|string|max:100']);

        $visit = $this->findOnSiteVisit($request->input('code'));

        if (! $visit) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'No visitor on site has that code. Check it and try again.']);
        }

        return redirect()->route('gate.checkout.show', $visit);
    }

    // The visitor's check-out page: what to collect, device status, confirm.
    public function checkoutShow(VisitorVisit $visit)
    {
        if ($visit->gate_checked_out_at) {
            return redirect()
                ->route('gate.checkout.done', $visit)
                ->with('error', "{$visit->name} is already checked out.");
        }

        $visit->load('devices');

        return view('gate.checkout-show', compact('visit'));
    }

    // Final step: two guards, two confirmations, then record the exit.
    public function checkout(Request $request, VisitorVisit $visit)
    {
        // Guard 1: already left.
        if ($visit->gate_checked_out_at) {
            return redirect()
                ->route('gate.checkout.done', $visit)
                ->with('error', "{$visit->name} is already checked out.");
        }

        // Guard 2: a device is still checked in on the premises.
        if ($visit->devices()->whereNull('device_checked_out_at')->exists()) {
            return back()->with('error', "{$visit->name} still has a device checked in. Check the device out at the Entry checkpoint first.");
        }

        // The officer must confirm both items were handed back / collected.
        $request->validate([
            'document_returned' => 'accepted',
            'card_collected' => 'accepted',
        ], [
            'document_returned.accepted' => 'Confirm that the ID document was returned to the visitor.',
            'card_collected.accepted' => 'Confirm that the visitor card was collected.',
        ]);

        $visit->update([
            'gate_checked_out_at' => now(),
            'document_returned_at' => now(),
        ]);

        return redirect()
            ->route('gate.checkout.done', $visit)
            ->with('success', "{$visit->name} checked out.");
    }

    // Summary of the completed visit.
    public function checkoutDone(VisitorVisit $visit)
    {
        // Not finished yet: send the officer back to the check-out page.
        if (! $visit->gate_checked_out_at) {
            return redirect()->route('gate.checkout.show', $visit);
        }

        $minutes = (int) abs(
            Carbon::parse($visit->gate_checked_in_at)->diffInMinutes(Carbon::parse($visit->gate_checked_out_at))
        );
        $duration = intdiv($minutes, 60) . ' h ' . ($minutes % 60) . ' min';

        return view('gate.checkout-done', compact('visit', 'duration'));
    }
}