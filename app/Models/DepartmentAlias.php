<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentAlias extends Model
{
    protected $fillable = ['alias_key', 'department_id'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}