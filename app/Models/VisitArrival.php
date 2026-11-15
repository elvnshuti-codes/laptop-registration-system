<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitArrival extends Model
{
    public const MODES = [
        'vehicle' => 'Vehicle',
        'motorcycle' => 'Motorcycle',
        'bicycle' => 'Bicycle',
        'foot' => 'On foot',
    ];

    protected $fillable = ['visitor_visit_id', 'mode', 'number_plate'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(VisitorVisit::class, 'visitor_visit_id');
    }

    // "Vehicle · UBA 123X", or just "On foot".
    public function label(): string
    {
        $name = self::MODES[$this->mode] ?? ucfirst($this->mode);

        return $this->number_plate ? "{$name} · {$this->number_plate}" : $name;
    }
}