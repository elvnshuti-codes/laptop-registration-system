<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One step waived by a senior officer in command.
class VisitWaiver extends Model
{
    public const STEPS = [
        'id_document' => 'ID document',
        'arms' => 'Arms left at the gate',
        'vip_details' => 'Details of the VIP being protected',
    ];

    public const REASONS = [
        'Declined, senior in rank',
        'Operational duty',
        'Instruction from higher authority',
        'Other',
    ];

    protected $fillable = [
        'visitor_visit_id', 'step', 'reason', 'note', 'recorded_by',
        'approved_at', 'approved_by', 'approved_after_exit',
    ];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'approved_after_exit' => 'boolean'];
    }

    public function label(): string
    {
        return self::STEPS[$this->step] ?? $this->step;
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(VisitorVisit::class, 'visitor_visit_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}