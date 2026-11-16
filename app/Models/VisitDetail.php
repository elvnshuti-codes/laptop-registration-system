<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitDetail extends Model
{
    protected $fillable = ['visitor_visit_id', 'department', 'person_to_see', 'details', 'recorded_by'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(VisitorVisit::class, 'visitor_visit_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}