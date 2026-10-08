<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorVisit extends Model
{
    // Every column a visit can be created or updated with, across all three checkpoints --
    // the Gate columns get filled at creation; the others get filled later via update().
    protected $fillable = [
        'visit_code',
        'name',
        'purpose',
        'purpose_other',
        'id_document_type',
        'id_document_other',
        'visitor_card_number',
        'gate_checked_in_at',
        'gate_checked_out_at',
        'no_device_confirmed_at',
        'document_returned_at',
    ];

    // A visit can have zero, one, or many devices registered against it (Standard House).
    public function devices()
    {
        return $this->hasMany(VisitorDevice::class);
    }

    // A visit can have reception data logged against it (Reception).
    public function receptions()
    {
        return $this->hasMany(VisitorReception::class);
    }
}