<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SampleType;
use App\Models\VisitArm;
use App\Models\VisitDetail;
use App\Models\VisitArrival;
use App\Models\VisitSecurityDetail;
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
    // How a Testing visitor's samples reached the premises.
    public const DELIVERED_BY = [
        'Owner' => 'Owner (brings own samples)',
        'Company representative' => 'Representative from the company',
        'Delivery person' => 'Delivery person (e.g. boda boda)',
        'Delivery company' => 'Delivery company / courier',
    ];

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

    // The Gate start screen: big tiles for what the officer does.
    public function home()
    {
        return view('gate.home');
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
        $testingPurposes = VisitPurpose::testingNames();
        $sampleTypes = SampleType::options();
        $deliveredBy = self::DELIVERED_BY;
        $arrivalModes = VisitArrival::MODES;
        $securityPurposes = VisitPurpose::securityNames();
        $forces = VisitSecurityDetail::FORCES;
        $ranks = VisitSecurityDetail::RANKS;
        $departments = Department::orderBy('name')->pluck('name');
        $armTypes = ['Firearms' => VisitArm::FIREARMS, 'Other weapons' => VisitArm::OTHERS];

        return view('gate.create', compact(
            'booking', 'purposes', 'testingPurposes', 'sampleTypes', 'deliveredBy', 'arrivalModes',
            'securityPurposes', 'forces', 'ranks', 'armTypes', 'departments'
        ));
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
        // Is this visitor bringing samples? Decided by the purpose's Sample Reception switch.
        $isTesting = VisitPurpose::testingNames()->contains($request->input('purpose'));

        // Is this a uniformed officer on security duty? Decided by the purpose's Security flag.
        $isSecurity = VisitPurpose::securityNames()->contains($request->input('purpose'));

        // Every other purpose (interview, meeting, delivery...) gets a details box.
        $needsDetails = ! $isTesting && ! $isSecurity;

        // If the owner is delivering, their own mobile is the owner's phone: no need to type it twice.
        if ($isTesting
            && $request->input('delivered_by_type') === 'Owner'
            && blank($request->input('sample_owner_phone'))
            && filled($request->input('visitor_mobile_number'))) {
            $request->merge(['sample_owner_phone' => $request->input('visitor_mobile_number')]);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'purpose' => ['required', 'string', 'max:255', Rule::exists('visit_purposes', 'name')],
            'purpose_other' => 'nullable|string|max:255',
            'id_document_type' => ['required', Rule::in(['National ID', 'Passport', 'Driving Permit', 'Other'])],
            'id_document_other' => 'nullable|required_if:id_document_type,Other|string|max:255',
            'id_document_number' => 'required|string|max:50',
            'visitor_card_number' => 'required|string|max:255',
            'visitor_mobile_number' => 'nullable|string|max:20',
            'booking_id' => 'nullable|exists:visitor_bookings,id',
        ];

        $rules += [
            'arrival_mode' => ['required', Rule::in(array_keys(VisitArrival::MODES))],
            'number_plate' => ['nullable', 'required_if:arrival_mode,vehicle,motorcycle', 'string', 'max:40'],
            'has_left_items' => 'nullable|boolean',
        ];

        // Belongings left at the gate: optional, but once chosen every row must be complete.
        if ($request->boolean('has_left_items')) {
            $rules += [
                'items' => 'required|array|min:1|max:20',
                'items.*.name' => 'required|string|max:120',
                'items.*.colour' => 'required|string|max:60',
                'items.*.quantity' => 'required|integer|min:1|max:999',
                'items.*.notes' => 'nullable|string|max:255',
            ];
        }

        if ($needsDetails) {
            $rules += [
                'detail_text' => 'required|string|max:500',
                'detail_department' => ['nullable', 'string', 'max:120', Rule::exists('departments', 'name')],
                'detail_person' => 'nullable|string|max:255',
            ];
        }

        // Security visitors: force, rank, the VIP they protect, and the arms they leave.
        if ($isSecurity) {
            $rules += [
                'security_force' => ['required', Rule::in(array_keys(VisitSecurityDetail::FORCES))],
                'officer_rank' => 'required|string|max:80',
                'vip_name' => 'required|string|max:255',
                'vip_title' => 'required|string|max:255',
                'locker_number' => 'nullable|string|max:40',
                'has_arms' => 'nullable|boolean',
            ];

            if ($request->boolean('has_arms')) {
                $rules += [
                    'arms' => 'required|array|min:1|max:20',
                    'arms.*.type' => ['required', Rule::in(VisitArm::TYPES)],
                    'arms.*.make' => ['nullable', 'string', 'max:120', 'required_if:arms.*.type,Other'],
                    'arms.*.serial' => ['nullable', 'string', 'max:60', 'distinct', 'required_if:arms.*.type,' . implode(',', VisitArm::FIREARMS)],
                    'arms.*.magazines' => 'nullable|integer|min:0|max:99',
                    'arms.*.count' => [
                        'required', 'integer', 'min:0', 'max:9999',
                        function ($attribute, $value, $fail) use ($request) {
                            $index = explode('.', $attribute)[1];
                            $type = $request->input("arms.$index.type");
                            if (! in_array($type, VisitArm::FIREARMS, true) && (int) $value < 1) {
                                $fail('Enter how many of each item that is not a firearm (at least 1).');
                            }
                        },
                    ],
                ];
            }
        }

        // Sample details are only required for Testing visitors.
        if ($isTesting) {
            $rules += [
                'samples' => 'required|array|min:1|max:10',
                'samples.*.type' => ['required', 'distinct', Rule::exists('sample_types', 'name')],
                'samples.*.count' => 'required|integer|min:1|max:9999',
                'sample_owner_name' => 'nullable|string|max:255',
                'sample_owner_company' => 'required|string|max:255',
                'sample_owner_tin' => 'nullable|string|max:30',
                'sample_owner_phone' => 'nullable|required_without:sample_owner_email|string|max:20',
                'sample_owner_email' => 'nullable|email|max:255',
                'sample_prn' => 'nullable|string|max:50',
                'delivered_by_type' => ['required', Rule::in(array_keys(self::DELIVERED_BY))],
                'delivered_by_company' => 'nullable|required_if:delivered_by_type,Company representative,Delivery company|string|max:255',
            ];

            // The person delivering must always be reachable.
            $rules['visitor_mobile_number'] = 'required|string|max:20';
        }

        $validated = $request->validate($rules, [
            'sample_owner_phone.required_without' => "Enter the owner's phone number or email (at least one is needed).",
            'samples.required' => 'Add at least one sample type.',
            'samples.*.type.required' => 'Choose a sample type for every row.',
            'samples.*.type.distinct' => 'Each sample type can only be listed once.',
            'samples.*.count.required' => 'Enter the number of samples for every row.',
            'visitor_mobile_number.required' => 'Enter the mobile number of the person delivering the samples.',
            'arrival_mode.required' => 'Choose how the visitor arrived.',
            'number_plate.required_if' => 'Enter the number plate.',
            'items.required' => 'Add at least one item left at the gate, or switch that option off.',
            'items.*.name.required' => 'Enter the item name for every row.',
            'items.*.colour.required' => 'Enter the colour of every item.',
            'items.*.quantity.required' => 'Enter how many of every item.',
            'detail_text.required' => 'Enter the details of the visit (for example: attending SACCO manager interviews).',
            'security_force.required' => 'Choose whether the officer belongs to the UPDF or the UPF.',
            'officer_rank.required' => "Enter the officer's rank.",
            'vip_name.required' => 'Enter the name of the VIP the officer is protecting.',
            'vip_title.required' => "Enter the VIP's title (e.g. Minister of Trade).",
            'arms.required' => 'Add at least one weapon, or switch that option off.',
            'arms.*.type.required' => 'Choose the type of every weapon.',
            'arms.*.make.required_if' => 'Describe the weapon when the type is Other.',
            'arms.*.serial.required_if' => 'Enter the serial number of every firearm.',
            'arms.*.serial.distinct' => 'The same serial number is listed twice.',
            'arms.*.count.required' => 'Enter the bullets (firearms) or the quantity (other items) for every row.',
        ]);

        // The sample rows are saved in their own table, not on the visit.
        $samples = $validated['samples'] ?? [];
        unset($validated['samples']);

        // Arrival and items are saved in their own tables, not on the visit.
        $arrival = [
            'mode' => $validated['arrival_mode'],
            'plate' => $validated['arrival_mode'] === 'foot' ? null : ($validated['number_plate'] ?? null),
        ];
        $leftItems = $request->boolean('has_left_items') ? ($validated['items'] ?? []) : [];
        $officerId = $request->user()->id;
        unset($validated['arrival_mode'], $validated['number_plate'], $validated['has_left_items'], $validated['items']);

        // Security details and arms also have their own tables.
        $security = null;
        $arms = [];
        if ($isSecurity) {
            $security = [
                'force' => $validated['security_force'],
                'rank' => trim($validated['officer_rank']),
                'vip_name' => trim($validated['vip_name']),
                'vip_title' => trim($validated['vip_title']),
                'locker' => filled($validated['locker_number'] ?? null) ? trim($validated['locker_number']) : null,
            ];
            $arms = $request->boolean('has_arms') ? ($validated['arms'] ?? []) : [];
        }
        // What the visitor came for (non-Testing, non-Security purposes).
        $detail = $needsDetails ? [
            'details' => trim($validated['detail_text']),
            'department' => filled($validated['detail_department'] ?? null) ? $validated['detail_department'] : null,
            'person' => filled($validated['detail_person'] ?? null) ? trim($validated['detail_person']) : null,
        ] : null;
        unset($validated['detail_text'], $validated['detail_department'], $validated['detail_person']);

        unset(
            $validated['security_force'], $validated['officer_rank'], $validated['vip_name'], $validated['vip_title'],
            $validated['locker_number'], $validated['has_arms'], $validated['arms']
        );

        $validated['id_document_number'] = strtoupper(trim($validated['id_document_number']));
        $validated['for_sample_reception'] = $isTesting;

        if ($isTesting) {
            if (! empty($validated['sample_prn'])) {
                $validated['sample_prn'] = strtoupper(trim($validated['sample_prn']));
            }

            // The person at the gate is the owner: no need to type the name twice.
            if ($validated['delivered_by_type'] === 'Owner') {
                $validated['sample_owner_name'] = $validated['sample_owner_name'] ?? $validated['name'];
                $validated['delivered_by_company'] = null;
            }
            // A delivery person on their own has no company to record.
            if ($validated['delivered_by_type'] === 'Delivery person') {
                $validated['delivered_by_company'] = null;
            }
        }

        $visit = DB::transaction(function () use ($validated, $samples, $arrival, $leftItems, $officerId, $security, $arms, $detail) {
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

            foreach ($samples as $row) {
                $visit->samples()->create([
                    'sample_type' => $row['type'],
                    'sample_count' => $row['count'],
                ]);
            }

            $visit->arrival()->create([
                'mode' => $arrival['mode'],
                'number_plate' => filled($arrival['plate']) ? strtoupper(preg_replace('/\s+/', ' ', trim($arrival['plate']))) : null,
            ]);

            foreach ($leftItems as $row) {
                $visit->leftItems()->create([
                    'item_name' => trim($row['name']),
                    'colour' => trim($row['colour']),
                    'quantity' => (int) $row['quantity'],
                    'notes' => filled($row['notes'] ?? null) ? trim($row['notes']) : null,
                    'handled_by' => $officerId,
                ]);
            }

            if ($detail) {
                $visit->detail()->create([
                    'details' => $detail['details'],
                    'department' => $detail['department'],
                    'person_to_see' => $detail['person'],
                    'recorded_by' => $officerId,
                ]);
            }

            if ($security) {
                $visit->security()->create([
                    'force' => $security['force'],
                    'officer_rank' => $security['rank'],
                    'vip_name' => $security['vip_name'],
                    'vip_title' => $security['vip_title'],
                    'locker_number' => $security['locker'],
                    'handled_by' => $officerId,
                ]);

                foreach ($arms as $row) {
                    $firearm = in_array($row['type'], VisitArm::FIREARMS, true);

                    $visit->arms()->create([
                        'arm_type' => $row['type'],
                        'make_model' => filled($row['make'] ?? null) ? trim($row['make']) : null,
                        'serial_number' => filled($row['serial'] ?? null) ? strtoupper(trim($row['serial'])) : null,
                        'magazines' => $firearm && filled($row['magazines'] ?? null) ? (int) $row['magazines'] : null,
                        'rounds' => $firearm ? (int) $row['count'] : null,
                        'quantity' => $firearm ? 1 : (int) $row['count'],
                    ]);
                }
            }

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
        $visit->load(['arrival', 'leftItems.handler', 'security.handler', 'arms', 'detail']);

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
            ->with(['devices', 'receptions', 'sampleReception', 'exceptions'])
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

        if (! $visit->entry_cleared_at) {
            return back()
                ->withInput()
                ->withErrors(['code' => "{$visit->name} has not been cleared at Entry. Send the visitor to Entry for device check-out first."]);
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

        // Strict order: Entry must clear the visitor before the Gate checks them out.
        if (! $visit->entry_cleared_at) {
            return redirect()
                ->route('gate.checkout.index')
                ->with('error', "{$visit->name} has not been cleared at Entry. Send the visitor to Entry for device check-out first.");
        }

        $visit->load(['devices', 'exceptions.recordedBy', 'arrival', 'leftItems.handler', 'security.handler', 'arms', 'detail']);

        return view('gate.checkout-show', compact('visit'));
    }

    // Final step: guards, two confirmations, then record the exit.
    public function checkout(Request $request, VisitorVisit $visit)
    {
        // Guard 1: already left.
        if ($visit->gate_checked_out_at) {
            return redirect()
                ->route('gate.checkout.done', $visit)
                ->with('error', "{$visit->name} is already checked out.");
        }

        // Guard 2: Entry must have cleared the visitor (devices out, service decision made).
        if (! $visit->entry_cleared_at) {
            return back()->with('error', "{$visit->name} has not been cleared at Entry. Send the visitor to Entry for device check-out first.");
        }

        // Guard 3: a device is still checked in on the premises.
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

        // Every belonging and weapon left at the gate must be accounted for:
        // ticked as handed back, or given a reason why it was not returned.
        $visit->load(['leftItems', 'arms']);

        $returnedItems = array_map('intval', (array) $request->input('returned_items', []));
        $returnedArms = array_map('intval', (array) $request->input('returned_arms', []));
        $itemReasons = (array) $request->input('reasons_items', []);
        $armReasons = (array) $request->input('reasons_arms', []);

        $problems = [];
        foreach ($visit->leftItems as $item) {
            $reason = trim((string) ($itemReasons[$item->id] ?? ''));
            if (! in_array($item->id, $returnedItems, true) && mb_strlen($reason) < 5) {
                $problems[] = "Tick \"{$item->item_name}\" as handed back, or write why it was not returned.";
            }
        }
        foreach ($visit->arms as $arm) {
            $reason = trim((string) ($armReasons[$arm->id] ?? ''));
            if (! in_array($arm->id, $returnedArms, true) && mb_strlen($reason) < 5) {
                $problems[] = "Tick {$arm->summary()} as handed back, or write why it was not returned.";
            }
        }

        if ($problems) {
            return back()->withInput()->withErrors(['returns' => $problems]);
        }

        DB::transaction(function () use ($request, $visit, $returnedItems, $returnedArms, $itemReasons, $armReasons) {
            $officerId = $request->user()->id;

            foreach ($visit->leftItems as $item) {
                $item->update(in_array($item->id, $returnedItems, true)
                    ? ['returned_at' => now(), 'returned_by' => $officerId, 'not_returned_reason' => null]
                    : ['returned_at' => null, 'returned_by' => $officerId, 'not_returned_reason' => trim($itemReasons[$item->id])]);
            }

            foreach ($visit->arms as $arm) {
                $arm->update(in_array($arm->id, $returnedArms, true)
                    ? ['returned_at' => now(), 'returned_by' => $officerId, 'not_returned_reason' => null]
                    : ['returned_at' => null, 'returned_by' => $officerId, 'not_returned_reason' => trim($armReasons[$arm->id])]);
            }

            $visit->update([
                'gate_checked_out_at' => now(),
                'document_returned_at' => now(),
            ]);
        });

        // Visitors who left things at the gate get a printable return receipt.
        if ($visit->leftItems->isNotEmpty() || $visit->arms->isNotEmpty()) {
            return redirect()
                ->route('gate.checkout.receipt', $visit)
                ->with('success', "{$visit->name} checked out.");
        }

        return redirect()
            ->route('gate.checkout.done', $visit)
            ->with('success', "{$visit->name} checked out.");
    }

    // Printable receipt: what was handed back to the visitor at check-out.
    public function receipt(VisitorVisit $visit)
    {
        if (! $visit->gate_checked_out_at) {
            return redirect()->route('gate.checkout.show', $visit);
        }

        $visit->load(['leftItems.handler', 'leftItems.returner', 'arms.returner', 'security.handler']);

        return view('gate.receipt', compact('visit'));
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