<?php

namespace App\Http\Controllers;

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
        return view('entry.index');
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

    // --- Device check-in --------------------------------------------------

    // Visitor check-in lookup: scanned or typed visit code.
    public function lookup(Request $request)
    {
        $request->validate(['code' => 'required|string|max:100']);

        $visit = $this->findOnSiteVisit($request->input('code'));

        if (! $visit) {
            return back()->withInput()->withErrors(['code' => self::NOT_FOUND]);
        }

        return redirect()->route('entry.visits.show', $visit);
    }

    // Visitor page: details, devices, and the register-device form.
    public function show(VisitorVisit $visit)
    {
        $visit->load('devices');

        return view('entry.show', compact('visit'));
    }

    // Registers one device: validates the scanned sticker, makes the device code, saves.
    public function storeDevice(Request $request, VisitorVisit $visit)
    {
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

        return redirect()->route('entry.visits.checkout', $visit);
    }

    // Shows the visitor's devices and asks for a sticker scan.
    public function checkoutShow(VisitorVisit $visit)
    {
        $visit->load('devices');

        return view('entry.checkout', compact('visit'));
    }

    // Verifies the scanned sticker belongs to THIS visitor, then checks the device out.
    public function checkoutDevice(Request $request, VisitorVisit $visit)
    {
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
}