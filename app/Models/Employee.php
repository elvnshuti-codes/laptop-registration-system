<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'employee_id_number', 'name', 'department', 'position', 'is_active', 'needs_review', 'review_note',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'needs_review' => 'boolean',
        ];
    }

    public function devices(): HasMany
    {
        return $this->hasMany(StaffDevice::class);
    }
}