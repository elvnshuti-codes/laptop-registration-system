<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorBooking extends Model
{
    protected $fillable = [
        'booking_code',
        'name',
        'visitor_mobile_number',
        'purpose',
        'purpose_other',
        'id_document_type',
        'id_document_other',
        'id_document_number',
        'meeting_with',
        'expected_date',
        'expected_time',
        'device_note',
        'status',
        'arrived_at',
        'visitor_visit_id',
        'created_by',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'arrived_at' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(VisitorVisit::class, 'visitor_visit_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}