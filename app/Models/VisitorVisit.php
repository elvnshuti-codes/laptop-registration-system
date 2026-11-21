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
        'site',
        'gate_note',
        'delivery_to',
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
        'entry_cleared_at',
        'entry_cleared_by',
    ];

    // Every visit belongs to one area: standard_house, sample_house or rig.
    protected static function booted(): void
    {
        static::creating(function (VisitorVisit $visit) {
            $visit->site ??= $visit->for_sample_reception ? 'sample_house' : 'standard_house';
        });
    }

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

    // Exceptions recorded against this visit (sent back, left unserved, skipped Entry).
    public function exceptions()
    {
        return $this->hasMany(VisitException::class);
    }

    // Has this visitor been served by THEIR reception?
    public function isServed(): bool
    {
        return $this->for_sample_reception
            ? $this->sampleReception()->exists()
            : $this->receptions()->whereNotNull('served_at')->exists();
    }

    // Served, or Entry has recorded why the visitor is leaving without being served.
    public function exitApproved(): bool
    {
        return $this->isServed() || $this->exceptions()->where('type', 'left_unserved')->exists();
    }

    // Where is this visitor right now? One word the dashboards can show.
    public function stage(): string
    {
        if ($this->gate_checked_out_at) {
            return 'Left';
        }
        if ($this->entry_cleared_at) {
            return 'Cleared at Entry';
        }
        if ($this->isServed()) {
            return 'Served';
        }
        if (! $this->for_sample_reception && $this->receptions()->exists()) {
            return 'At Reception';
        }
        if ($this->no_device_confirmed_at || $this->devices()->exists()) {
            return 'Passed Entry';
        }

        return 'At Gate';
    }

    public function arrival(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\VisitArrival::class);
    }

    public function leftItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\VisitLeftItem::class);
    }

    public function security(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\VisitSecurityDetail::class);
    }

    public function arms(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\VisitArm::class);
    }
    public function waivers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\VisitWaiver::class);
    }

    public function detail(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\VisitDetail::class);
    }
}