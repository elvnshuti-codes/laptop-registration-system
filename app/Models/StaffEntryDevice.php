<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffEntryDevice extends Model
{
    protected $fillable = [
        'staff_entry_id', 'staff_device_id', 'open_device_id', 'checked_out_at',
        'close_reason', 'admin_request_id',
    ];

    protected function casts(): array
    {
        return ['checked_out_at' => 'datetime'];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(StaffEntry::class, 'staff_entry_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(StaffDevice::class, 'staff_device_id');
    }

    // The Admin approval this device was admitted with, if any.
    public function adminRequest(): BelongsTo
    {
        return $this->belongsTo(StaffDeviceRequest::class, 'admin_request_id');
    }
}