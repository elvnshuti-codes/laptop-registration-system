<?php

namespace App\Http\Controllers;

use App\Models\VisitorVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GateController extends Controller
{

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

            $sequence = DB::table('visitor_ticket_sequences')
                ->where('ticket_date', $today)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                DB::table('visitor_ticket_sequences')->insert([
                    'ticket_date' => $today,
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $nextNumber = 1;
            } else {
                $nextNumber = $sequence->last_number + 1;
            }

            DB::table('visitor_ticket_sequences')
                ->where('ticket_date', $today)
                ->update(['last_number' => $nextNumber, 'updated_at' => now()]);

            $visitCode = sprintf('UNBS/V%03d/%s', $nextNumber, now()->format('D'));

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
}