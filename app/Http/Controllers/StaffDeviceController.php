<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\StaffDevice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffDeviceController extends Controller
{
    public const OWNER_TYPES = ['organization' => 'Organization', 'personal' => 'Personal'];

    // Device type => barcode prefix for personal devices.
    public const DEVICE_TYPES = ['Laptop' => 'PL', 'Tablet' => 'PT'];

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

        $request->merge([
            'make_model' => trim((string) $request->input('make_model')),
            'serial_number' => $this->clean($request->input('serial_number')),
            'asset_tag' => $isOrganization ? $this->clean($request->input('asset_tag')) : null,
        ]);

        $data = $request->validate([
            'owner_type' => ['required', Rule::in(array_keys(self::OWNER_TYPES))],
            'device_type' => ['required', Rule::in(array_keys(self::DEVICE_TYPES))],
            'make_model' => 'nullable|string|max:100',
            'serial_number' => ['required', 'string', 'max:60', 'regex:/^[A-Z0-9._\/-]+$/', 'unique:staff_devices,serial_number'],
            'asset_tag' => [
                Rule::requiredIf($isOrganization), 'nullable', 'string', 'max:60',
                'regex:/^[A-Z0-9._\/-]+$/', 'not_regex:/^P[LT]-/', 'unique:staff_devices,asset_tag',
            ],
        ], [
            'serial_number.regex' => 'The serial number may only contain letters, digits and - / . _ characters.',
            'asset_tag.regex' => 'The asset tag may only contain letters, digits and - / . _ characters.',
            'asset_tag.not_regex' => 'An asset tag cannot start with PL- or PT-, those are reserved for personal devices.',
            'asset_tag.required' => 'The UNBS asset tag is required for an organization device.',
        ]);

        if ($message = $this->limitMessage($employee, $data['owner_type'])) {
            return back()->withInput()->withErrors(['owner_type' => $message]);
        }

        // What the barcode will read.
        $code = $isOrganization
            ? $data['asset_tag']
            : self::DEVICE_TYPES[$data['device_type']] . '-' . $data['serial_number'];

        if (StaffDevice::where('sticker_code', $code)->exists()) {
            return back()->withInput()->withErrors(['serial_number' => "A device with the barcode {$code} is already registered."]);
        }

        $device = $employee->devices()->create($data + [
            'sticker_code' => $code,
            'make_model' => $data['make_model'] ?: null,
            'is_active' => true,
            'registered_by' => $request->user()->id,
        ]);

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

    // Serial numbers and asset tags: uppercase, no spaces (scanners do not reproduce spaces reliably).
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