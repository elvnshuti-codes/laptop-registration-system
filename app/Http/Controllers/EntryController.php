<?php

namespace App\Http\Controllers;

use App\Models\UnservedReason;
use App\Models\User;
use App\Models\VisitException;
use App\Models\VisitorDevice;
use App\Models\VisitorVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EntryController extends Controller
{
    private const NOT_FOUND = 'No visitor on site has that code. Check it and try again.';

    // Checkpoint 2 start screen (Staff / Visitor).
    public function index()
    {
        return view('entry.index', [
            // Staff currently inside at this checkpoint (null when the officer cannot handle staff devices).
            'staffInside' => auth()->user()->can('manage staff entry')
                ? \App\Models\StaffEntry::whereNull('checked_out_at')->where('location', 'entry')->count()
                : null,
        ]);
    }

    // --- Location ---------------------------------------------------------

    // Which visitors may this officer handle?
    // true = Sample House (Testing visitors), false = Standard House, null = Administrator (both).
    private function forSampleHouse(): ?bool
    {
        $user = auth()->user();

        if ($user->hasRole('Administrator')) {
            return null;
        }

        return $user->location === 'Sample House';
    }

    // A clear message when the visitor belongs to the other building, otherwise null.
    private function locationMismatch(VisitorVisit $visit): ?string
    {
        $mine = $this->forSampleHouse();

        if ($mine === null || (bool) $visit->for_sample_reception === $mine) {
            return null;
        }

        return $visit->for_sample_reception
            ? 'This visitor is for the Sample House, not Standard House.'
            : 'This visitor is for Standard House, not the Sample House.';
    }

    // Blocks a hand-typed address for a visitor of the other location.
    private function guardLocation(VisitorVisit $visit): void
    {
        if ($message = $this->locationMismatch($visit)) {
            abort(403, $message);
        }
    }

    // Finds a visitor currently on site from a scanned or typed visit code.
    private function findOnSiteVisit(string $code): ?VisitorVisit
    {
        // Barcodes carry hyphens, printed codes carry slashes: treat them the same.
        $code = str_replace('/', '-', trim($code));

        return VisitorVisit::whereNull('gate_checked_out_at')
            ->whereRaw("REPLACE(visit_code, '/', '-') = ?", [$code])
            ->first();
    }

    // The receptionists Entry may name as "spoke to": the accounts that serve this
    // visitor's reception, shown exactly as their account names.
    private function receptionNames(VisitorVisit $visit): array
    {
        $permission = $visit->for_sample_reception ? 'manage sample reception' : 'manage visitor reception';

        return User::permission($permission)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Administrator'))
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    // --- Device check-in --------------------------------------------------

    // Visitor check-in lookup: scanned or typed visit code.
    public function lookup(Request $request)
    {
        $request->validate(['code' => 'required|string|max:100']);

        $visit = $this->findOnSiteVisit($request->input('code'));

        if (! $visit) {
            return back()->withInput()->withErrors(['code' => self::NOT_FOUND]);
        }

        if ($message = $this->locationMismatch($visit)) {
            return back()->withInput()->withErrors(['code' => $message]);
        }

        return redirect()->route('entry.visits.show', $visit);
    }

    // Visitor page: details, devices, and the register-device form.
    public function show(VisitorVisit $visit)
    {
        $this->guardLocation($visit);

        $visit->load('devices');

        return view('entry.show', compact('visit'));
    }

    // Registers one device: validates the scanned sticker, makes the device code, saves.
    public function storeDevice(Request $request, VisitorVisit $visit)
    {
        $this->guardLocation($visit);

        if ($visit->gate_checked_out_at) {
            return back()->with('error', 'This visitor has already checked out at the gate.');
        }

        if ($visit->no_device_confirmed_at) {
            return back()->with('error', 'This visitor was confirmed as having no device.');
        }

        // Scanners can add spaces or change case: tidy the sticker before validating.
        $request->merge(['sticker_code' => strtoupper(trim((string) $request->input('sticker_code')))]);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'asset_tag'   => 'required|string|max:255',
            'sticker_code' => [
                'required',
                'string',
                Rule::exists('visitor_stickers', 'sticker_code'),
                Rule::unique('visitor_devices', 'sticker_code'),
            ],
        ], [
            'sticker_code.exists' => 'That is not a genuine UNBS visitor sticker.',
            'sticker_code.unique' => 'That sticker is already in use on another device.',
        ]);

        $device = DB::transaction(function () use ($validated, $visit) {
            $today = now()->toDateString();

            // Same locked-counter pattern as the visit codes.
            DB::table('visitor_device_sequences')->insertOrIgnore([
                'device_date' => $today,
                'last_number' => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $sequence = DB::table('visitor_device_sequences')
                ->where('device_date', $today)
                ->lockForUpdate()
                ->first();

            $nextNumber = $sequence->last_number + 1;

            DB::table('visitor_device_sequences')
                ->where('id', $sequence->id)
                ->update(['last_number' => $nextNumber, 'updated_at' => now()]);

            $deviceCode = sprintf('UNBS-D-%s-%03d', now()->format('Ymd'), $nextNumber);

            return VisitorDevice::create([
                'visitor_visit_id'     => $visit->id,
                'device_code'          => $deviceCode,
                'asset_tag'            => $validated['asset_tag'],
                'description'          => $validated['description'],
                'sticker_code'         => $validated['sticker_code'],
                'device_checked_in_at' => now(),
            ]);
        });

        return redirect()
            ->route('entry.visits.show', $visit)
            ->with('success', "Device registered: {$device->device_code}");
    }

    // Records that the visitor arrived with no device.
    public function noDevice(VisitorVisit $visit)
    {
        $this->guardLocation($visit);

        if ($visit->devices()->exists()) {
            return back()->with('error', 'This visitor already has a device registered.');
        }

        $visit->update(['no_device_confirmed_at' => now()]);

        return redirect()
            ->route('entry.visits.show', $visit)
            ->with('success', 'Confirmed: visitor has no device.');
    }

    // --- Device check-out -------------------------------------------------

    // Visitor check-out lookup: scanned or typed visit code.
    public function checkoutLookup(Request $request)
    {
        $request->validate(['checkout_code' => 'required|string|max:100']);

        $visit = $this->findOnSiteVisit($request->input('checkout_code'));

        if (! $visit) {
            return back()->withInput()->withErrors(['checkout_code' => self::NOT_FOUND]);
        }

        if ($message = $this->locationMismatch($visit)) {
            return back()->withInput()->withErrors(['checkout_code' => $message]);
        }

        return redirect()->route('entry.visits.checkout', $visit);
    }

    // Shows the visitor's devices, their service status, and what Entry can do next.
    public function checkoutShow(VisitorVisit $visit)
    {
        $this->guardLocation($visit);

        $visit->load('devices');

        $served = $visit->isServed();
        $approved = $visit->exitApproved();
        $area = $visit->for_sample_reception ? 'Sample Reception' : 'Standard House Reception';
        $reasons = UnservedReason::orderBy('is_system')->orderBy('name')->get();
        $receptionists = $this->receptionNames($visit);
        $sentBackAt = optional($visit->exceptions()->where('type', 'sent_back')->latest()->first())->created_at;
        $leftUnserved = $visit->exceptions()->where('type', 'left_unserved')->latest()->first();

        return view('entry.checkout', compact('visit', 'served', 'approved', 'area', 'reasons', 'receptionists', 'sentBackAt', 'leftUnserved'));
    }

    // Verifies the scanned sticker belongs to THIS visitor, then checks the device out.
    public function checkoutDevice(Request $request, VisitorVisit $visit)
    {
        $this->guardLocation($visit);

        // Devices are released only after Entry has decided about an unserved visitor.
        if (! $visit->exitApproved()) {
            return back()->withErrors(['sticker_code' => 'This visitor has not been served. Send them back to Reception, or record why they are leaving, before releasing devices.']);
        }

        $request->merge(['sticker_code' => strtoupper(trim((string) $request->input('sticker_code')))]);
        $request->validate(['sticker_code' => 'required|string|max:100']);

        $code = $request->input('sticker_code');

        // Is this sticker on one of THIS visitor's devices?
        $device = $visit->devices()->where('sticker_code', $code)->first();

        if (! $device) {
            $belongsToSomeoneElse = VisitorDevice::where('sticker_code', $code)->exists();

            $message = $belongsToSomeoneElse
                ? 'This sticker belongs to a DIFFERENT visitor. Do not release this device.'
                : 'This sticker is not registered to this visitor.';

            return back()->withErrors(['sticker_code' => $message]);
        }

        if ($device->device_checked_out_at) {
            $time = \Carbon\Carbon::parse($device->device_checked_out_at)->format('H:i');

            return back()->withErrors(['sticker_code' => "This device was already checked out at {$time}."]);
        }

        $device->update(['device_checked_out_at' => now()]);

        return redirect()
            ->route('entry.visits.checkout', $visit)
            ->with('success', "Device checked out: {$device->description} ({$device->device_code})");
    }

    // --- Clearing a visitor for exit --------------------------------------

    // Why Entry cannot clear this visitor right now (null = fine to continue).
    private function clearanceBlock(VisitorVisit $visit): ?string
    {
        if ($visit->gate_checked_out_at) {
            return "{$visit->name} has already left the premises.";
        }

        if ($visit->entry_cleared_at) {
            return "{$visit->name} is already cleared for exit.";
        }

        return null;
    }

    // Records the clearance. A visitor who never went through Entry at check-in is logged first.
    private function clearForExit(VisitorVisit $visit): void
    {
        $update = [];

        if (! $visit->no_device_confirmed_at && ! $visit->devices()->exists()) {
            VisitException::create([
                'visitor_visit_id' => $visit->id,
                'type' => 'skipped_entry',
                'area' => $visit->for_sample_reception ? 'Sample House' : 'Standard House',
                'reason' => 'Did not go through Entry at check-in',
                'last_stage' => $visit->stage(),
                'recorded_by' => auth()->id(),
            ]);

            $update['no_device_confirmed_at'] = now();
        }

        $visit->update($update + [
            'entry_cleared_at' => now(),
            'entry_cleared_by' => auth()->id(),
        ]);
    }

    // Served (or reason recorded) and all devices out: one click releases them to the Gate.
    public function clear(VisitorVisit $visit)
    {
        $this->guardLocation($visit);

        if ($message = $this->clearanceBlock($visit)) {
            return back()->with('error', $message);
        }

        if (! $visit->exitApproved()) {
            return back()->with('error', 'This visitor has not been served. Send them back, or record why they are leaving.');
        }

        if ($visit->devices()->whereNull('device_checked_out_at')->exists()) {
            return back()->with('error', 'Release all of the visitor\'s devices first.');
        }

        $this->clearForExit($visit);

        return redirect()
            ->route('entry.visits.checkout', $visit)
            ->with('success', "{$visit->name} cleared. Send the visitor to the Gate to check out.");
    }

    // Not served: tell the visitor to go back to their reception, and log it.
    public function sendBack(VisitorVisit $visit)
    {
        $this->guardLocation($visit);

        if ($visit->gate_checked_out_at) {
            return back()->with('error', "{$visit->name} has already left the premises.");
        }

        if ($visit->isServed()) {
            return back()->with('error', 'This visitor has already been served.');
        }

        if ($visit->exceptions()->where('type', 'left_unserved')->exists()) {
            return back()->with('error', 'A reason for leaving was already recorded for this visitor.');
        }

        $area = $visit->for_sample_reception ? 'Sample House' : 'Standard House';

        VisitException::create([
            'visitor_visit_id' => $visit->id,
            'type' => 'sent_back',
            'area' => $area,
            'last_stage' => $visit->stage(),
            'recorded_by' => auth()->id(),
        ]);

        $reception = $visit->for_sample_reception ? 'Sample Reception' : 'Standard House Reception';

        return redirect()
            ->route('entry.visits.checkout', $visit)
            ->with('success', "Send {$visit->name} to {$reception} to be served.");
    }

    // Not served and leaving anyway: record the reason and the Reception contact. Devices are released next.
    public function unserved(Request $request, VisitorVisit $visit)
    {
        $this->guardLocation($visit);

        if ($message = $this->clearanceBlock($visit)) {
            return back()->with('error', $message);
        }

        if ($visit->isServed()) {
            return back()->with('error', 'This visitor was served. Use "Clear for Gate exit".');
        }

        if ($visit->exceptions()->where('type', 'left_unserved')->exists()) {
            return back()->with('error', 'A reason for leaving was already recorded for this visitor.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', Rule::exists('unserved_reasons', 'name')],
            'phone_confirmed' => 'accepted',
            'confirmed_with' => ['required', 'string', Rule::in($this->receptionNames($visit))],
            'note' => 'nullable|string|max:500',
        ], [
            'reason.required' => 'Choose the reason the visitor did not reach Reception.',
            'phone_confirmed.accepted' => 'Confirm that you contacted Reception and verified the reason.',
            'confirmed_with.required' => 'Choose who you spoke to at Reception.',
            'confirmed_with.in' => 'Choose a name from the list.',
        ]);

        $reason = UnservedReason::where('name', $validated['reason'])->first();

        if ($reason->requires_note && blank($validated['note'] ?? null)) {
            return back()->withInput()->withErrors(['note' => 'Please add a short note for this reason.']);
        }

        VisitException::create([
            'visitor_visit_id' => $visit->id,
            'type' => 'left_unserved',
            'area' => $visit->for_sample_reception ? 'Sample House' : 'Standard House',
            'reason' => $validated['reason'],
            'note' => $validated['note'] ?? null,
            'phone_confirmed' => true,
            'confirmed_with' => $validated['confirmed_with'],
            'last_stage' => $visit->stage(),
            'recorded_by' => auth()->id(),
        ]);

        return redirect()
            ->route('entry.visits.checkout', $visit)
            ->with('success', "Reason recorded for {$visit->name}. Now release the visitor's devices.");
    }
}