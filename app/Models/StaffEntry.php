<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffEntry extends Model
{
    protected $fillable = [
        'token', 'employee_id', 'open_employee_id', 'location',
        'checked_in_by', 'checked_in_at', 'checked_out_by', 'checked_out_at',
        'close_reason', 'closed_by_admin',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(StaffEntryDevice::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    // Still open, and opened on an earlier day: the employee left without checking out.
    public function isStale(): bool
    {
        return $this->checked_out_at === null && ! $this->checked_in_at->isToday();
    }
}