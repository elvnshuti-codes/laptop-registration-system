<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorSample extends Model
{
    protected $fillable = ['visitor_visit_id', 'sample_type', 'sample_count'];

    public function visit()
    {
        return $this->belongsTo(VisitorVisit::class, 'visitor_visit_id');
    }
}