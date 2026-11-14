<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffEntryDevice extends Model
{
    protected $fillable = ['staff_entry_id', 'staff_device_id', 'open_device_id', 'checked_out_at'];

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
}