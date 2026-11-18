<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDeviceRequest extends Model
{
    public const TYPES = [
        'stale_entry' => 'Device not checked out',
        'issued_device' => 'Issued device never checked out',
    ];

    protected $fillable = [
        'type', 'employee_id', 'staff_entry_id', 'staff_device_id', 'device_ids', 'details',
        'requested_by', 'status', 'open_key', 'decided_by', 'decided_at', 'decision_note',
        'used_at', 'released_at', 'released_by',
    ];

    protected function casts(): array
    {
        return [
            'device_ids' => 'array',
            'decided_at' => 'datetime',
            'used_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function reference(): string
    {
        return 'DR-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(StaffEntry::class, 'staff_entry_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(StaffDevice::class, 'staff_device_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}