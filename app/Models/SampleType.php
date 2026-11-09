<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SampleType extends Model
{
    protected $fillable = ['name'];

    // Names for the Gate dropdown, alphabetical.
    public static function options()
    {
        return static::orderBy('name')->pluck('name');
    }
}