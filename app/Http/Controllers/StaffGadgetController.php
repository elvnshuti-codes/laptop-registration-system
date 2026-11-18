<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\GadgetSticker;
use App\Models\StaffDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Entry officer: register a personal gadget that is not in the system, using a pre-printed sticker.
class StaffGadgetController extends Controller
{
    public const TYPES = ['Laptop', 'Tablet', 'Phone', 'Camera', 'Hard drive', 'Power bank', 'Other'];

    public function store(Request $request, Employee $employee)
    {
        abort_unless($employee->is_active, 404);

        $data = $request->validate([
            'device_type' => 'required|in:' . implode(',', self::TYPES),
            'make_model' => 'required|string|max:100',
            'colour' => 'required|string|max:40',
            'seal_number' => 'nullable|string|max:60',
            'sticker_code' => 'required|string|max:60',
        ], [
            'make_model.required' => 'Write the name or make of the gadget.',
            'sticker_code.required' => 'Scan the new gadget sticker.',
        ]);

        $code = strtoupper(trim($data['sticker_code']));

        $device = DB::transaction(function () use ($request, $employee, $data, $code) {
            $sticker = GadgetSticker::where('sticker_code', $code)->lockForUpdate()->first();

            if (! $sticker) {
                return 'No walk-in gadget sticker has that code. Use a sticker printed by Admin (UNBS-WG-...).';
            }
            if ($sticker->status !== 'unused') {
                return "{$code} has already been used. Take a fresh sticker.";
            }

            $device = StaffDevice::create([
                'employee_id' => $employee->id,
                'sticker_code' => $code,
                'owner_type' => 'personal',
                'device_type' => $data['device_type'],
                'make_model' => $data['make_model'],
                'serial_number' => $code,           // a gadget with no known serial uses its sticker code
                'colour' => $data['colour'],
                'seal_number' => $data['seal_number'] ?? null,
                'is_walk_in' => true,
                'is_active' => true,
                'sticker_printed_at' => now(),
                'registered_by' => $request->user()->id,
            ]);

            $sticker->update(['status' => 'used', 'staff_device_id' => $device->id, 'used_at' => now()]);

            return $device;
        });

        if (is_string($device)) {
            return back()->withErrors(['sticker_code' => $device])->withInput();
        }

        return redirect()->route('entry.staff.show', $employee)
            ->with('success', "{$device->label()} registered to {$employee->name}. Scan its sticker below to check it in.");
    }
}