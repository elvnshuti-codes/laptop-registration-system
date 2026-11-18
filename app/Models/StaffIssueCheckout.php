<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// An issued device leaving the premises without ever having been checked in.
class StaffIssueCheckout extends Model
{
    protected $fillable = [
        'staff_device_id', 'employee_id', 'issued_at', 'collected_at', 'location',
        'checked_out_by', 'checked_out_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'collected_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(StaffDevice::class, 'staff_device_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }
}