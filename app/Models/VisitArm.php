<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitArm extends Model
{
    // Firearms: serial number and bullets are recorded.
    public const FIREARMS = [
        'Pistol', 'Revolver', 'Rifle', 'Sub-machine gun', 'Light machine gun', 'Shotgun',
    ];

    // Other weapons: a quantity is recorded; "Other" must be described.
    public const OTHERS = [
        'Knife', 'Bayonet', 'Tear gas', 'Pepper spray', 'Baton', 'Taser', 'Handcuffs', 'Hand grenade', 'Other',
    ];

    public const TYPES = [...self::FIREARMS, ...self::OTHERS];

    protected $fillable = [
        'visitor_visit_id', 'arm_type', 'make_model', 'serial_number', 'magazines', 'rounds', 'quantity',
        'returned_at', 'returned_by', 'not_returned_reason',
        'approved_at', 'approved_by', 'approved_after_exit',
    ];

    protected $casts = [
        'returned_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_after_exit' => 'boolean',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(VisitorVisit::class, 'visitor_visit_id');
    }

    // The supervisor who verified this record.
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // The Gate officer who handed it back (or recorded why not).
    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function isFirearm(): bool
    {
        return in_array($this->arm_type, self::FIREARMS, true);
    }

    // "Pistol · Glock 17 · SN 123 · 2 magazines · 30 rounds"  or  "Knife × 2"
    public function summary(): string
    {
        $isOther = $this->arm_type === 'Other';
        $parts = [$isOther ? ($this->make_model ?: 'Other') : $this->arm_type];

        if (! $isOther && $this->make_model) {
            $parts[] = $this->make_model;
        }
        if ($this->serial_number) {
            $parts[] = 'SN ' . $this->serial_number;
        }

        if ($this->isFirearm()) {
            if ($this->magazines !== null) {
                $parts[] = $this->magazines . ' ' . ($this->magazines === 1 ? 'magazine' : 'magazines');
            }
            $parts[] = (int) $this->rounds . ' ' . ((int) $this->rounds === 1 ? 'round' : 'rounds');
        } elseif ($this->quantity > 1) {
            $parts[0] .= ' × ' . $this->quantity;
        }

        return implode(' · ', $parts);
    }
}