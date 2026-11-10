<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnservedReason extends Model
{
    protected $fillable = ['name', 'requires_note', 'is_system'];

    protected function casts(): array
    {
        return [
            'requires_note' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
