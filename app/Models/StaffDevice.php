<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDevice extends Model
{
    protected $fillable = [
        'employee_id', 'sticker_code', 'owner_type', 'device_type', 'make_model',
        'serial_number', 'asset_tag', 'is_active', 'sticker_printed_at', 'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sticker_printed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}