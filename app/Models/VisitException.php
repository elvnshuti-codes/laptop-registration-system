<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitException extends Model
{
    protected $fillable = [
        'visitor_visit_id', 'type', 'area', 'reason', 'note',
        'phone_confirmed', 'confirmed_with', 'last_stage',
        'recorded_by', 'acknowledged_by', 'acknowledged_at',
    ];

    protected $casts = [
        'phone_confirmed' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(VisitorVisit::class, 'visitor_visit_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
