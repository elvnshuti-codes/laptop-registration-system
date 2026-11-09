<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SampleReception extends Model
{
    protected $fillable = ['visitor_visit_id', 'served_by', 'reference_number', 'note', 'served_at'];

    protected $casts = [
        'served_at' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(VisitorVisit::class, 'visitor_visit_id');
    }

    public function servedBy()
    {
        return $this->belongsTo(User::class, 'served_by');
    }
}