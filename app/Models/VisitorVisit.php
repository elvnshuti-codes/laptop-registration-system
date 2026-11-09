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
        'id_document_number',
        'visitor_mobile_number',
        'visitor_card_number',
        'for_sample_reception',
        'sample_owner_name',
        'sample_owner_company',
        'sample_owner_phone',
        'sample_owner_email',
        'sample_owner_tin',
        'sample_prn',
        'delivered_by_type',
        'delivered_by_company',
        'gate_checked_in_at',
        'gate_checked_out_at',
        'no_device_confirmed_at',
        'document_returned_at',
    ];
    
    protected $casts = [
        'for_sample_reception' => 'boolean',
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
        // The sample types and counts a Testing visitor brought.
        public function samples()
        {
            return $this->hasMany(VisitorSample::class);
        }
            // Set once Sample Reception has served this visitor.
       
            public function sampleReception()
        {
        return $this->hasOne(SampleReception::class);
        }
}