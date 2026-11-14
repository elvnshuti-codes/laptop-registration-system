<?php

namespace App\Http\Controllers;

use App\Models\Employee;
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
        return view('entry.staff.show', [
            'employee' => $employee,
            'open' => StaffEntry::where('employee_id', $employee->id)->whereNull('checked_out_at')->first(),
            'devices' => $employee->devices()->where('is_active', true)->orderBy('owner_type')->orderBy('device_type')->get(),
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

        try {
            $entry = DB::transaction(function () use ($request, $employee, $devices) {
                $entry = StaffEntry::create([
                    'token' => 'ST-' . Str::upper(Str::random(10)),
                    'employee_id' => $employee->id,
                    'open_employee_id' => $employee->id,
                    'location' => self::LOCATION,
                    'checked_in_by' => $request->user()->id,
                    'checked_in_at' => now(),
                ]);

                foreach ($devices as $device) {
                    $entry->devices()->create(['staff_device_id' => $device->id, 'open_device_id' => $device->id]);
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