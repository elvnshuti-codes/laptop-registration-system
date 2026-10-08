<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorDevice extends Model
{
    // visitor_visit_id is fillable here because we set it ourselves when creating a device
    // row (e.g. VisitorDevice::create(['visitor_visit_id' => $visit->id, ...])) --
    // it isn't auto-filled the way a belongsTo() foreign key sometimes can be.
    protected $fillable = [
        'visitor_visit_id',
        'device_code',
        'asset_tag',
        'device_checked_in_at',
        'device_checked_out_at',
    ];

    // The foreign key -- a device belongs to exactly one visit.
    public function visit()
    {
        return $this->belongsTo(VisitorVisit::class);
    }
}