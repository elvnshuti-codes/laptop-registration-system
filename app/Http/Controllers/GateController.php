<?php

namespace App\Http\Controllers;

use App\Models\VisitorBooking;
use App\Models\VisitorVisit;
use App\Models\VisitPurpose;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GateController extends Controller
{
    // --- Check-in ---------------------------------------------------------

    // Returns a plain-language reason why a booking cannot be used today,
    // or null when it is fine.
    private function bookingProblem(?VisitorBooking $booking): ?string
    {
        if (! $booking) {
            return 'No booking found with that code. Check it and try again.';
        }
        if ($booking->status === 'cancelled') {
            return "Booking {$booking->booking_code} was cancelled.";
        }
        if ($booking->status === 'arrived') {
            return "Booking {$booking->booking_code} has already been used.";
        }
        if ($booking->expected_date->isBefore(today())) {
            return "Booking {$booking->booking_code} has expired (it was for {$booking->expected_date->format('d M Y')}).";
        }
        if ($booking->expected_date->isAfter(today())) {
            return "Booking {$booking->booking_code} is for {$booking->expected_date->format('d M Y')}, not today.";
        }

        return null;
    }

    // Shows the Gate check-in form. With ?booking=CODE the form is pre-filled.
    public function create(Request $request)
    {
        $booking = null;

        if ($request->filled('booking')) {
            $booking = VisitorBooking::where('booking_code', strtoupper(trim($request->input('booking'))))->first();

            if ($problem = $this->bookingProblem($booking)) {
                return redirect()->route('gate.create')->with('error', $problem);
            }
        }

        $purposes = VisitPurpose::options();

        return view('gate.create', compact('booking', 'purposes'));
    }

    // Scan or type a booking code to open the check-in form pre-filled.
    public function bookingLookup(Request $request)
    {
        $request->validate(['booking_code' => 'required|string|max:50']);

        $code = strtoupper(trim($request->input('booking_code')));
        $booking = VisitorBooking::where('booking_code', $code)->first();

        if ($problem = $this->bookingProblem($booking)) {
            return back()->withInput()->withErrors(['booking_code' => $problem]);
        }

        return redirect()->route('gate.create', ['booking' => $booking->booking_code]);
    }

    // Handles form submission: validates, generates the ticket number
    // inside a locked transaction, and creates the visit.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'purpose' => ['required', 'string', 'max:255', Rule::exists('visit_purposes', 'name')],
            'purpose_other' => 'nullable|string|max:255',
            'id_document_type' => ['required', Rule::in(['National ID', 'Passport', 'Driving Permit', 'Other'])],
            'id_document_other' => 'nullable|required_if:id_document_type,Other|string|max:255',
            'id_document_number' => 'required|string|max:50',
            'visitor_card_number' => 'required|string|max:255',
            'visitor_mobile_number' => 'nullable|string|max:20',
            'booking_id' => 'nullable|exists:visitor_bookings,id',
        ]);

        $validated['id_document_number'] = strtoupper(trim($validated['id_document_number']));

        $visit = DB::transaction(function () use ($validated) {
            // If this visitor came with a booking, lock it and re-check it,
            // so one booking can never be used twice.
            $booking = null;
            if (! empty($validated['booking_id'])) {
                $booking = VisitorBooking::lockForUpdate()->find($validated['booking_id']);

                if ($problem = $this->bookingProblem($booking)) {
                    throw ValidationException::withMessages(['booking_id' => $problem]);
                }
            }

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

            $visit = VisitorVisit::create([
                ...Arr::except($validated, ['booking_id']),
                'visit_code' => $visitCode,
                'gate_checked_in_at' => now(),
            ]);

            if ($booking) {
                $booking->update([
                    'status' => 'arrived',
                    'arrived_at' => now(),
                    'visitor_visit_id' => $visit->id,
                ]);
            }

            return $visit;
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