<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\StaffDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class StaffDeviceController extends Controller
{
    public const OWNER_TYPES = ['organization' => 'Organization', 'personal' => 'Personal'];
    public const DEVICE_TYPES = ['Laptop', 'Tablet'];

    public const MAX_ORGANIZATION = 1;
    public const MAX_PERSONAL = 2;

    // One employee's devices, and the form to register another.
    public function index(Employee $employee)
    {
        $devices = $employee->devices()->orderByDesc('is_active')->orderBy('owner_type')->orderBy('id')->get();
        $active = $devices->where('is_active', true);

        return view('staff.devices.index', [
            'employee' => $employee,
            'devices' => $devices,
            'organizationFull' => $active->where('owner_type', 'organization')->count() >= self::MAX_ORGANIZATION,
            'personalFull' => $active->where('owner_type', 'personal')->count() >= self::MAX_PERSONAL,
            'ownerTypes' => self::OWNER_TYPES,
            'deviceTypes' => self::DEVICE_TYPES,
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        if (! $employee->is_active) {
            return back()->withErrors(['owner_type' => 'This employee is inactive. Activate them before registering a device.']);
        }

        $isOrganization = $request->input('owner_type') === 'organization';

        $serial = $this->clean($request->input('serial_number'));
        $tag = $isOrganization ? $this->clean($request->input('asset_tag')) : '';
        $model = trim((string) $request->input('make_model'));

        $request->merge([
            'make_model' => $model === '' ? null : $model,
            'serial_number' => $serial === '' ? null : $serial,
            'asset_tag' => $tag === '' ? null : $tag,
        ]);

        $data = $request->validate([
            'owner_type' => ['required', Rule::in(array_keys(self::OWNER_TYPES))],
            'device_type' => ['required', Rule::in(self::DEVICE_TYPES)],
            'make_model' => 'nullable|string|max:100',
            'serial_number' => [
                Rule::requiredIf(! $isOrganization), 'nullable', 'string', 'max:60',
                'regex:/^[A-Z0-9._\/-]+$/', 'unique:staff_devices,serial_number',
            ],
            'asset_tag' => [
                Rule::requiredIf($isOrganization), 'nullable', 'string', 'max:60',
                'regex:/^[A-Z0-9._\/-]+$/', 'unique:staff_devices,asset_tag',
            ],
        ], [
            'serial_number.required' => 'The serial number is required for a personal device.',
            'serial_number.regex' => 'The serial number may only contain letters, digits and - / . _ characters.',
            'asset_tag.required' => 'The UNBS asset tag is required for an organization device.',
            'asset_tag.regex' => 'The asset tag may only contain letters, digits and - / . _ characters.',
        ]);

        if ($message = $this->limitMessage($employee, $data['owner_type'])) {
            return back()->withInput()->withErrors(['owner_type' => $message]);
        }

        $device = DB::transaction(function () use ($employee, $data, $request) {

            // The row is created first so its own number is available for the code.
            $device = $employee->devices()->create($data + [
                'sticker_code' => 'TMP-' . Str::uuid(),
                'is_active' => true,
                'registered_by' => $request->user()->id,
            ]);

            $device->update([
                'sticker_code' => StaffDevice::makeCode($data['owner_type'], $data['device_type'], $data['asset_tag'], $device->id),
            ]);

            return $device;
        });

        return redirect()
            ->route('staff.devices.index', $employee)
            ->with('success', "Device registered. Its barcode reads: {$device->sticker_code}");
    }

    // Retire a device (or bring it back) instead of deleting it, so past records keep their owner.
    public function toggle(StaffDevice $device)
    {
        if (! $device->is_active && ($message = $this->limitMessage($device->employee, $device->owner_type, $device->id))) {
            return back()->withErrors(['device' => $message]);
        }

        $device->update(['is_active' => ! $device->is_active]);

        return back()->with('success', "{$device->sticker_code} " . ($device->is_active ? 'is active again.' : 'was retired.'));
    }

    
    // Serial numbers and asset tags: uppercase, no spaces.
    private function clean(mixed $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $value));
    }

    // Null when the employee can still have another active device of this kind.
    private function limitMessage(Employee $employee, string $ownerType, ?int $ignoreId = null): ?string
    {
        $count = $employee->devices()
            ->where('is_active', true)
            ->where('owner_type', $ownerType)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->count();

        $max = $ownerType === 'organization' ? self::MAX_ORGANIZATION : self::MAX_PERSONAL;

        if ($count < $max) {
            return null;
        }

        return $ownerType === 'organization'
            ? 'This employee already has an active UNBS device. Retire it first to register another.'
            : "This employee already has {$max} active personal devices. Retire one first.";
    }
}