<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\StaffEntry;
use App\Models\StaffEntryDevice;
use App\Models\StaffIssueCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Check-out of staff devices at Entry. A device must leave from the checkpoint where it came in.
class StaffCheckoutController extends Controller
{
    private const LOCATION = 'entry';

    // Scan a staff ticket, or search for the employee. Everyone still inside is listed.
    public function start(Request $request)
    {
        $ticket = strtoupper(trim((string) $request->query('ticket')));
        $q = trim((string) $request->query('q'));

        if ($ticket !== '') {
            $entry = StaffEntry::where('token', $ticket)->first();

            if (! $entry) {
                return redirect()->route('entry.staff-out.start')
                    ->withErrors(['ticket' => 'No staff ticket has that code. Scan again or search by name.']);
            }

            return redirect()->route('entry.staff-out.show', $entry->employee_id);
        }

        $results = collect();
        if ($q !== '') {
            $query = Employee::where('is_active', true);
            foreach (preg_split('/\s+/', $q) as $word) {
                $query->where(fn ($w) => $w->where('name', 'like', "%{$word}%")
                    ->orWhere('employee_id_number', 'like', "%{$word}%"));
            }
            $results = $query->orderBy('name')->limit(15)->get();
        }

        $inside = StaffEntry::with(['employee', 'devices.device'])
            ->whereNull('checked_out_at')->orderBy('checked_in_at')->get();

        return view('entry.staff.checkout-start', compact('q', 'results', 'inside'));
    }

    // One employee: what is on their open entry, and any device issued to them that never checked in.
    public function show(Employee $employee)
    {
        $entry = StaffEntry::with(['devices.device', 'devices.adminRequest', 'checkedInBy'])
            ->where('employee_id', $employee->id)->whereNull('checked_out_at')->first();

        $wrongPlace = $entry !== null && $entry->location !== self::LOCATION;
        $onEntry = ($entry && ! $wrongPlace) ? $entry->devices->whereNotNull('open_device_id') : collect();
        $issued = $employee->devices()->where('is_active', true)->issuedOpen()->get();

        return view('entry.staff.checkout-show', compact('employee', 'entry', 'wrongPlace', 'onEntry', 'issued'));
    }

    public function store(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'codes' => 'required|array|min:1',
            'codes.*' => 'string|max:60',
        ], ['codes.required' => 'Scan at least one device sticker.']);

        $codes = array_values(array_unique(array_map(fn ($code) => strtoupper(trim($code)), $data['codes'])));

        $entry = StaffEntry::with('devices.device')
            ->where('employee_id', $employee->id)->whereNull('checked_out_at')->first();

        // Only an entry made at this checkpoint can be closed here.
        $onEntry = ($entry && $entry->location === self::LOCATION)
            ? $entry->devices->whereNotNull('open_device_id')->keyBy(fn ($row) => strtoupper($row->device->sticker_code))
            : collect();

        $issued = $employee->devices()->where('is_active', true)->issuedOpen()->get()
            ->keyBy(fn ($device) => strtoupper($device->sticker_code));

        foreach ($codes as $code) {
            if (! $onEntry->has($code) && ! $issued->has($code)) {
                return back()->withErrors([
                    'codes' => "{$code} is not on this person's open entry here, and it is not a device issued to them. Check the sticker.",
                ]);
            }
        }

        $slipIds = [];
        $rowIds = [];

        DB::transaction(function () use ($request, $entry, $onEntry, $issued, $codes, &$slipIds, &$rowIds) {
            foreach ($codes as $code) {
                if ($onEntry->has($code)) {
                    $onEntry[$code]->update(['checked_out_at' => now(), 'open_device_id' => null]);
                    $rowIds[] = $onEntry[$code]->id;
                    continue;
                }

                // Issued during the day and never checked in: record the check-out with its issue time.
                $device = $issued[$code];
                $row = StaffIssueCheckout::create([
                    'staff_device_id' => $device->id,
                    'employee_id' => $device->employee_id,
                    'issued_at' => $device->issued_at,
                    'collected_at' => $device->collected_at,
                    'location' => self::LOCATION,
                    'checked_out_by' => $request->user()->id,
                    'checked_out_at' => now(),
                ]);
                $device->update(['issue_closed_at' => now()]);
                $slipIds[] = $row->id;
            }

            // The entry closes by itself once nothing is left on it.
            if ($entry && $entry->location === self::LOCATION
                && ! $entry->devices()->whereNotNull('open_device_id')->exists()) {
                $entry->update([
                    'checked_out_at' => now(),
                    'checked_out_by' => $request->user()->id,
                    'open_employee_id' => null,
                ]);
            }
        });

        // Every check-out ends on a summary screen the officer can print.
        return redirect()->route('entry.staff-out.slip', [
            'ids' => implode(',', $slipIds),
            'rows' => implode(',', $rowIds),
            'emp' => $employee->id,
        ])->with('success', "{$employee->name}: check-out recorded.");
    }

    // Summary of what just left: devices that were checked in, and devices issued without a check-in.
    public function slip(Request $request)
    {
        $parse = fn ($key) => collect(explode(',', (string) $request->query($key)))
            ->map(fn ($id) => (int) $id)->filter()->unique()->values();

        $issuedIds = $parse('ids');
        $rowIds = $parse('rows');

        abort_if($issuedIds->isEmpty() && $rowIds->isEmpty(), 404);

        $issuedRows = StaffIssueCheckout::with(['device', 'employee', 'checkedOutBy'])->whereIn('id', $issuedIds)->get();
        $entryRows = StaffEntryDevice::with(['device', 'entry.employee'])->whereIn('id', $rowIds)->get();

        $employee = $issuedRows->first()?->employee ?? $entryRows->first()?->entry?->employee;
        abort_if($employee === null, 404);

        return view('entry.staff.issued-slip', compact('issuedRows', 'entryRows', 'employee'));
    }
}