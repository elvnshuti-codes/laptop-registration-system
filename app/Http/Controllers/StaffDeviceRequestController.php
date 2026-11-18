<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\StaffDevice;
use App\Models\StaffDeviceRequest;
use App\Models\StaffEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Requests from Entry to Admin when a UNBS device comes back without having been checked out.
class StaffDeviceRequestController extends Controller
{
    // Closes an open entry with a reason: every device still on it is closed too.
    public static function closeEntry(StaffEntry $entry, string $reason, ?User $admin): void
    {
        DB::transaction(function () use ($entry, $reason, $admin) {
            $entry->devices()->whereNotNull('open_device_id')->update([
                'checked_out_at' => now(),
                'open_device_id' => null,
                'close_reason' => $reason,
            ]);

            $entry->update([
                'checked_out_at' => now(),
                'checked_out_by' => $admin?->id,
                'open_employee_id' => null,
                'close_reason' => $reason,
                'closed_by_admin' => $admin?->id,
            ]);
        });
    }

    // --- Entry officer: send a request ------------------------------------

    public function store(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'type' => 'required|in:stale_entry,issued_device',
            'staff_entry_id' => 'nullable|integer',
            'staff_device_id' => 'nullable|integer',
            'kept' => 'accepted',
        ], ['kept.accepted' => 'Tick "I am keeping the device" before sending the request.']);

        if ($data['type'] === 'stale_entry') {
            $entry = StaffEntry::with('devices.device')
                ->where('id', $data['staff_entry_id'] ?? 0)
                ->where('employee_id', $employee->id)
                ->whereNull('checked_out_at')->firstOrFail();

            abort_unless($entry->isStale(), 404);

            $unbs = $entry->devices->filter(fn ($row) => $row->open_device_id && $row->device->owner_type === 'organization');
            $key = 'E' . $entry->id;
            $deviceId = null;
            $deviceIds = $unbs->pluck('staff_device_id')->all();
            $details = 'Checked in ' . $entry->checked_in_at->format('d M Y, H:i') . ' at ' . ucfirst($entry->location)
                . ' and never checked out. UNBS devices still on that entry: '
                . $unbs->map(fn ($row) => $row->device->label())->implode(', ') . '.';
        } else {
            $device = $employee->devices()->findOrFail($data['staff_device_id'] ?? 0);
            abort_unless($device->isIssuedOpen(), 404);

            $entry = null;
            $key = 'D' . $device->id;
            $deviceId = $device->id;
            $deviceIds = [$device->id];
            $details = $device->label() . ' was issued on ' . $device->issued_at->format('d M Y, H:i')
                . ($device->collected_at ? ', collected ' . $device->collected_at->format('d M Y, H:i') : ', not yet collected')
                . ', and was never checked out.';
        }

        // One open request at a time for the same entry or device. An old approval that was never used expires.
        $existing = StaffDeviceRequest::where('open_key', $key)->first();
        if ($existing && $existing->status === 'approved' && ! ($existing->decided_at && $existing->decided_at->isToday())) {
            $existing->update(['status' => 'expired', 'open_key' => null]);
            $existing = null;
        }
        if ($existing) {
            return back()->with('error', "A request ({$existing->reference()}) is already open for this device. Keep the device until Admin answers.");
        }

        $created = StaffDeviceRequest::create([
            'type' => $data['type'],
            'employee_id' => $employee->id,
            'staff_entry_id' => $entry?->id,
            'staff_device_id' => $deviceId,
            'device_ids' => $deviceIds,
            'details' => $details,
            'requested_by' => $request->user()->id,
            'status' => 'pending',
            'open_key' => $key,
        ]);

        return redirect()->route('entry.staff.show', $employee)
            ->with('success', "Request {$created->reference()} sent to Admin. Keep the device until Admin answers.");
    }

    // --- Admin ------------------------------------------------------------

    public function index()
    {
        $with = ['employee', 'requester', 'decider', 'entry.checkedInBy', 'device'];

        return view('admin.device-requests.index', [
            'pending' => StaffDeviceRequest::with($with)->where('status', 'pending')->orderBy('created_at')->get(),
            'held' => StaffDeviceRequest::with($with)->where('status', 'declined')->whereNull('released_at')->latest('decided_at')->get(),
            'recent' => StaffDeviceRequest::with($with)->whereIn('status', ['approved', 'used', 'expired'])
                ->orWhere(fn ($q) => $q->where('status', 'declined')->whereNotNull('released_at'))
                ->latest('id')->limit(20)->get(),
        ]);
    }

    public function approve(Request $request, StaffDeviceRequest $deviceRequest)
    {
        abort_unless($deviceRequest->status === 'pending', 404);

        $note = $request->validate(['decision_note' => 'nullable|string|max:255'])['decision_note'] ?? null;

        DB::transaction(function () use ($request, $deviceRequest, $note) {
            if ($deviceRequest->type === 'stale_entry' && $deviceRequest->staff_entry_id) {
                $entry = StaffEntry::lockForUpdate()->find($deviceRequest->staff_entry_id);
                if ($entry && $entry->checked_out_at === null) {
                    self::closeEntry($entry, 'admin_override', $request->user());
                }
            }

            if ($deviceRequest->type === 'issued_device' && $deviceRequest->staff_device_id) {
                StaffDevice::whereKey($deviceRequest->staff_device_id)->update(['issue_closed_at' => now()]);
            }

            $deviceRequest->update([
                'status' => 'approved',
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);
        });

        return back()->with('success', "{$deviceRequest->reference()} approved. The Entry officer can now check the device in.");
    }

    public function decline(Request $request, StaffDeviceRequest $deviceRequest)
    {
        abort_unless($deviceRequest->status === 'pending', 404);

        $note = $request->validate(['decision_note' => 'nullable|string|max:255'])['decision_note'] ?? null;

        $deviceRequest->update([
            'status' => 'declined',
            'open_key' => null,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);

        return back()->with('success', "{$deviceRequest->reference()} declined. The device stays held at Entry until you mark it handed over.");
    }

    // A declined device that was held at Entry has now been handed over or returned.
    public function release(Request $request, StaffDeviceRequest $deviceRequest)
    {
        abort_unless($deviceRequest->status === 'declined' && $deviceRequest->released_at === null, 404);

        $deviceRequest->update(['released_at' => now(), 'released_by' => $request->user()->id]);

        return back()->with('success', "{$deviceRequest->reference()} marked as handed over.");
    }
}