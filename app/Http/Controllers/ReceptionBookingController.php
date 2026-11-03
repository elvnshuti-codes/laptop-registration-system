<?php

namespace App\Http\Controllers;

use App\Models\VisitorBooking;
use App\Models\VisitPurpose;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReceptionBookingController extends Controller
{
    // Letters and digits without 0/O and 1/I, so a code cannot be misread.
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private function makeCode(): string
    {
        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $code = 'BK-' . $suffix;
        } while (VisitorBooking::where('booking_code', $code)->exists());

        return $code;
    }

    // Bookings from the last 7 days onward, soonest first.
    public function index()
    {
        $bookings = VisitorBooking::with('createdBy')
            ->where('expected_date', '>=', today()->subDays(7))
            ->orderBy('expected_date')
            ->orderBy('expected_time')
            ->get();

        return view('reception.bookings.index', compact('bookings'));
    }

    public function create()
    {
        $purposes = VisitPurpose::options();

        return view('reception.bookings.create', compact('purposes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'visitor_mobile_number' => 'nullable|string|max:20',
            'purpose' => ['required', Rule::exists('visit_purposes', 'name')],
            'purpose_other' => 'nullable|string|max:255',
            'id_document_type' => 'required|in:National ID,Passport,Driving Permit,Other',
            'id_document_other' => 'required_if:id_document_type,Other|nullable|string|max:255',
            'id_document_number' => 'required|string|max:50',
            'meeting_with' => 'required|string|max:255',
            'expected_date' => 'required|date|after_or_equal:today',
            'expected_time' => 'nullable|date_format:H:i',
            'device_note' => 'nullable|string|max:500',
        ], [
            'id_document_type.required' => 'Choose the ID document the visitor will carry.',
            'id_document_number.required' => 'Enter the document number. The visitor must carry this document on arrival.',
            'id_document_other.required_if' => 'Enter the name of the other document.',
        ]);

        // Document numbers are compared and printed in capitals, without stray spaces.
        $validated['id_document_number'] = strtoupper(trim($validated['id_document_number']));

        $booking = VisitorBooking::create([
            ...$validated,
            'booking_code' => $this->makeCode(),
            'status' => 'expected',
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('reception.bookings.show', $booking)
            ->with('success', 'Booking created. Give the code below to the visitor.');
    }

    // The booking slip: big code and barcode to hand over or send to the visitor.
    public function show(VisitorBooking $booking)
    {
        $booking->load('createdBy');

        return view('reception.bookings.show', compact('booking'));
    }

    public function cancel(VisitorBooking $booking)
    {
        if ($booking->status !== 'expected') {
            return back()->with('error', 'Only an expected booking can be cancelled.');
        }

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', "Booking {$booking->booking_code} cancelled.");
    }
}