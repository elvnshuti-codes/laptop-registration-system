<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitLeftItem extends Model
{
    protected $fillable = [
        'visitor_visit_id', 'item_name', 'colour', 'quantity', 'notes', 'handled_by',
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

    // The Gate officer who took the item.
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
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
}