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
}