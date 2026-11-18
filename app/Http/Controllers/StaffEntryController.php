<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\StaffDeviceRequest;
use App\Models\StaffEntry;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Check-in of staff devices at a checkpoint. Entry for now; Rig will reuse this with its own location.
class StaffEntryController extends Controller
{
    private const LOCATION = 'entry';

    // Find an employee, and see who is inside at this location.
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $results = collect();

        if ($q !== '') {
            $query = Employee::where('is_active', true);

            // Every word typed must appear in the name or ID: "elve nshuti" finds "Nshuti Elve".
            foreach (preg_split('/\s+/', $q) as $word) {
                $query->where(fn ($w) => $w->where('name', 'like', "%{$word}%")
                    ->orWhere('employee_id_number', 'like', "%{$word}%"));
            }

            $results = $query
                ->withCount(['devices as device_count' => fn ($d) => $d->where('is_active', true)])
                ->orderBy('name')->limit(15)->get();
        }

        $inside = StaffEntry::with(['employee', 'devices.device'])
            ->whereNull('checked_out_at')->where('location', self::LOCATION)
            ->orderByDesc('checked_in_at')->get();

        return view('entry.staff.index', compact('q', 'results', 'inside'));
    }

    // One employee: confirm who they are and choose the devices they bring in.
    public function show(Employee $employee)
    {
        $open = StaffEntry::with(['devices.device', 'checkedInBy'])
            ->where('employee_id', $employee->id)->whereNull('checked_out_at')->first();

        // An entry from an earlier day that was never closed: if only personal gadgets are left on it,
        // it closes by itself. UNBS devices left on it must be cleared by Admin.
        if ($open && $open->isStale()) {
            $unbsOpen = $open->devices->filter(
                fn ($row) => $row->open_device_id && $row->device->owner_type === 'organization'
            );

            if ($unbsOpen->isEmpty()) {
                StaffDeviceRequestController::closeEntry($open, 'auto_personal', null);
                $open = null;
            }
        }

        $stale = $open !== null && $open->isStale();
        $devices = $employee->devices()->where('is_active', true)->orderBy('owner_type')->orderBy('device_type')->get();

        $staleDevices = $stale
            ? $open->devices->filter(fn ($row) => $row->open_device_id && $row->device->owner_type === 'organization')
            : collect();

        $staleRequest = $stale
            ? StaffDeviceRequest::where('type', 'stale_entry')->where('staff_entry_id', $open->id)->latest('id')->first()
            : null;

        // The latest request for each issued device (pending, declined or approved today).
        $deviceRequests = StaffDeviceRequest::where('type', 'issued_device')
            ->whereIn('staff_device_id', $devices->pluck('id'))
            ->whereIn('status', ['pending', 'declined', 'approved'])
            ->latest('id')->get()
            ->filter(fn ($r) => $r->status !== 'approved' || ($r->decided_at && $r->decided_at->isToday()))
            ->unique('staff_device_id')->keyBy('staff_device_id');

        // Approved today and not yet used: the devices may now be checked in.
        $approvedToday = StaffDeviceRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')->whereNull('used_at')->latest('id')->get()
            ->filter(fn ($r) => $r->decided_at && $r->decided_at->isToday());

        return view('entry.staff.show', [
            'employee' => $employee,
            'open' => $open,
            'stale' => $stale,
            'devices' => $devices,
            'staleDevices' => $staleDevices,
            'staleRequest' => $staleRequest,
            'deviceRequests' => $deviceRequests,
            'approvedToday' => $approvedToday,
            'issuedOpenIds' => $devices->filter(fn ($d) => $d->isIssuedOpen())->pluck('id')->all(),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        abort_unless($employee->is_active, 404);

        $data = $request->validate([
            'codes' => 'required|array|min:1',
            'codes.*' => 'string|max:60',
        ], ['codes.required' => 'Scan at least one device sticker.']);

        // Only scanned stickers count: each code must be the sticker of one of this employee's active devices.
        $codes = array_values(array_unique(array_map(fn ($code) => strtoupper(trim($code)), $data['codes'])));
        $devices = $employee->devices()->where('is_active', true)->whereIn('sticker_code', $codes)->get();

        if ($devices->count() !== count($codes)) {
            return back()->withErrors(['codes' => 'A scanned sticker does not belong to this employee, or the device was retired.']);
        }

        // Already inside, or an old entry that was never checked out: no new check-in until that is dealt with.
        $open = StaffEntry::where('employee_id', $employee->id)->whereNull('checked_out_at')->first();
        if ($open) {
            $message = $open->isStale()
                ? 'Not checked out since ' . $open->checked_in_at->format('d M Y, H:i') . '. Check-in is refused until Admin clears it.'
                : 'This person is already checked in.';

            return redirect()->route('entry.staff.show', $employee)->withErrors(['devices' => $message]);
        }

        // UNBS devices that were issued to the employee and never checked out are refused.
        $blocked = $devices->filter(fn ($device) => $device->isIssuedOpen());
        if ($blocked->isNotEmpty()) {
            $list = $blocked->map(fn ($device) => $device->label() . ' (issued ' . $device->issued_at->format('d M Y, H:i') . ')')->implode(', ');

            return redirect()->route('entry.staff.show', $employee)->withErrors([
                'devices' => "Refused: {$list} was issued and never checked out. Keep the device and send a request to Admin.",
            ]);
        }

        // Approvals from Admin, given today and not yet used, cover these devices.
        $covered = [];
        $approved = StaffDeviceRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')->whereNull('used_at')->get()
            ->filter(fn ($r) => $r->decided_at && $r->decided_at->isToday());

        foreach ($approved as $approval) {
            if ($approval->type === 'issued_device' && $approval->staff_device_id) {
                $covered[$approval->staff_device_id] = $approval;
            }
            if ($approval->type === 'stale_entry') {
                foreach ((array) $approval->device_ids as $deviceId) {
                    $covered[$deviceId] = $approval;
                }
            }
        }

        try {
            $entry = DB::transaction(function () use ($request, $employee, $devices, $covered) {
                $entry = StaffEntry::create([
                    'token' => 'ST-' . Str::upper(Str::random(10)),
                    'employee_id' => $employee->id,
                    'open_employee_id' => $employee->id,
                    'location' => self::LOCATION,
                    'checked_in_by' => $request->user()->id,
                    'checked_in_at' => now(),
                ]);

                $usedRequestIds = [];
                foreach ($devices as $device) {
                    $approval = $covered[$device->id] ?? null;
                    $entry->devices()->create([
                        'staff_device_id' => $device->id,
                        'open_device_id' => $device->id,
                        'admin_request_id' => $approval?->id,
                    ]);

                    if ($approval) {
                        $usedRequestIds[] = $approval->id;
                    }
                }

                if ($usedRequestIds) {
                    StaffDeviceRequest::whereIn('id', array_unique($usedRequestIds))
                        ->update(['status' => 'used', 'used_at' => now(), 'open_key' => null]);
                }

                return $entry;
            });
        } catch (QueryException $e) {
            // 1062 = the database refused a duplicate open entry (this person, or one of these devices, is already inside).
            if (($e->errorInfo[1] ?? null) === 1062) {
                return redirect()->route('entry.staff.show', $employee)
                    ->withErrors(['devices' => 'This person, or one of these devices, is already checked in.']);
            }

            throw $e;
        }

        return redirect()->route('entry.staff.ticket', $entry)->with('success', "{$employee->name} checked in with {$devices->count()} device(s).");
    }

    // The ticket to print.
    public function ticket(StaffEntry $entry)
    {
        return view('entry.staff.ticket', ['entry' => $entry->load(['employee', 'devices.device', 'checkedInBy'])]);
    }
}