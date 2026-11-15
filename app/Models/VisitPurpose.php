<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitPurpose extends Model
{
    protected $fillable = ['name', 'is_system', 'is_testing'];

    protected $casts = [
        'is_system' => 'boolean',
        'is_testing' => 'boolean',
    ];

    // Names for the dropdowns, alphabetical, with the built-in "Other" always last.
    public static function options()
    {
        return static::orderBy('is_system')->orderBy('name')->pluck('name');
    }

    // Names of the purposes that send visitors to Sample Reception.
    public static function testingNames()
    {
        return static::where('is_testing', true)->pluck('name');
    }

        // Purposes flagged as Security (uniformed officers on duty).
        public static function securityNames()
        {
            return static::where('is_security', true)->pluck('name');
        }
}