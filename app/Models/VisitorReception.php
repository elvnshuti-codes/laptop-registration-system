<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorReception extends Model
{
    protected $fillable = [
        'visitor_visit_id',
        'destination',
        'meeting_with',
        'purpose_confirmed',
        'served',
        'received_by',
    ];

    // A reception record belongs to exactly one visit -- default foreign key
    // guess (visitor_visit_id) matches our actual column, so no extra arguments needed.
    public function visit()
    {
        return $this->belongsTo(VisitorVisit::class);
    }

    // A reception record belongs to the staff User who handled it. Laravel's default
    // guess for belongsTo(User::class) would be "user_id" -- but our column is named
    // "received_by", so we pass it explicitly as the second argument.
    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}