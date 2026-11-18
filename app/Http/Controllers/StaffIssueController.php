<?php

namespace App\Http\Controllers;

use App\Mail\StaffDevicesCollected;
use App\Models\StaffDevice;
use App\Models\StaffEntryDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Devices issued to an employee during the day, before they were ever checked in.
class StaffIssueController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $results = collect();

        if ($q !== '') {
            $query = StaffDevice::with('employee')->where('is_active', true)->where('owner_type', 'organization');

            foreach (preg_split('/\s+/', $q) as $word) {
                $query->where(fn ($w) => $w
                    ->where('sticker_code', 'like', "%{$word}%")
                    ->orWhere('asset_tag', 'like', "%{$word}%")
                    ->orWhere('serial_number', 'like', "%{$word}%")
                    ->orWhereHas('employee', fn ($e) => $e->where('name', 'like', "%{$word}%")
                        ->orWhere('employee_id_number', 'like', "%{$word}%")));
            }

            $results = $query->orderBy('sticker_code')->limit(15)->get();
        }

        $awaiting = StaffDevice::with(['employee', 'issuer'])->issuedOpen()->whereNull('collected_at')->orderBy('issued_at')->get();
        $collected = StaffDevice::with(['employee', 'collector'])->issuedOpen()->whereNotNull('collected_at')->orderBy('collected_at')->get();

        return view('staff.issued.index', compact('q', 'results', 'awaiting', 'collected'));
    }

    public function issue(Request $request, StaffDevice $device)
    {
        if (! $device->is_active || $device->owner_type !== 'organization') {
            return back()->with('error', 'Only active UNBS devices can be issued.');
        }
        if ($device->isIssuedOpen()) {
            return back()->with('error', "{$device->label()} is already issued and has not been checked out.");
        }
        if (StaffEntryDevice::where('staff_device_id', $device->id)->whereNotNull('open_device_id')->exists()) {
            return back()->with('error', "{$device->label()} is currently checked in at Entry.");
        }

        $device->update([
            'issued_at' => now(),
            'issued_by' => $request->user()->id,
            'collected_at' => null,
            'collected_by' => null,
            'issue_closed_at' => null,
        ]);

        return back()->with('success', "{$device->label()} issued to {$device->employee->name} at " . now()->format('H:i') . '.');
    }

    // The owner has collected the device: email them everything registered to them.
    public function collect(Request $request, StaffDevice $device)
    {
        if (! $device->isIssuedOpen() || $device->collected_at !== null) {
            return back()->with('error', 'This device is not waiting for collection.');
        }

        $device->update(['collected_at' => now(), 'collected_by' => $request->user()->id]);

        $employee = $device->employee;

        if (blank($employee->email)) {
            return back()->with('success', "{$device->label()} collected by {$employee->name}. No email address is on record, so no email was sent.");
        }

        try {
            Mail::to($employee->email)->send(new StaffDevicesCollected($employee, $device));
        } catch (\Throwable $e) {
            Log::warning('Collected email failed: ' . $e->getMessage());

            return back()->with('error', "{$device->label()} marked as collected, but the email to {$employee->email} could not be sent.");
        }

        return back()->with('success', "{$device->label()} collected by {$employee->name}. An email was sent to {$employee->email}.");
    }
}