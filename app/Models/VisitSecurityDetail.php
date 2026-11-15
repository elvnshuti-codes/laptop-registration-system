<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitSecurityDetail extends Model
{
    protected $table = 'visit_security_details';

    public const FORCES = [
        'UPDF' => 'UPDF (Uganda People\'s Defence Forces)',
        'UPF' => 'UPF (Uganda Police Force)',
    ];

    // Suggestions only: the officer's rank is typed, so unusual ranks still work.
    public const RANKS = [
        'UPDF' => [
            'Private', 'Lance Corporal', 'Corporal', 'Sergeant', 'Staff Sergeant',
            'Warrant Officer II', 'Warrant Officer I', 'Second Lieutenant', 'Lieutenant',
            'Captain', 'Major', 'Lieutenant Colonel', 'Colonel', 'Brigadier',
            'Major General', 'Lieutenant General', 'General',
        ],
        'UPF' => [
            'Police Constable', 'Corporal', 'Sergeant', 'Senior Sergeant',
            'Assistant Inspector of Police', 'Inspector of Police', 'Chief Inspector of Police',
            'Assistant Superintendent of Police', 'Superintendent of Police',
            'Senior Superintendent of Police', 'Assistant Commissioner of Police',
            'Senior Commissioner of Police', 'Commissioner of Police',
        ],
    ];

    protected $fillable = [
        'visitor_visit_id', 'force', 'officer_rank', 'vip_name', 'vip_title', 'locker_number', 'handled_by',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(VisitorVisit::class, 'visitor_visit_id');
    }

    // The Gate officer who took the arms in.
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}