<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A pre-printed sticker for a personal gadget that was not in the system.
class GadgetSticker extends Model
{
    public const PREFIX = 'UNBS-WG-';

    protected $fillable = ['sticker_code', 'status', 'staff_device_id', 'created_by', 'used_at'];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(StaffDevice::class, 'staff_device_id');
    }
}