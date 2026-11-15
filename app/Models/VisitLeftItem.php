<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitLeftItem extends Model
{
    protected $fillable = ['visitor_visit_id', 'item_name', 'colour', 'quantity', 'notes', 'handled_by'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(VisitorVisit::class, 'visitor_visit_id');
    }

    // The Gate officer who took the item.
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}