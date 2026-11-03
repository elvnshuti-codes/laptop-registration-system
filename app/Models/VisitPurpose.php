<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitPurpose extends Model
{
    protected $fillable = ['name', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    // Names for the dropdowns, alphabetical, with the built-in "Other" always last.
    public static function options()
    {
        return static::orderBy('is_system')->orderBy('name')->pluck('name');
    }
}