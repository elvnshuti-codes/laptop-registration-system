<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaptopRegistration extends Model
{
    protected $fillable = [
    'employee_name',
    'employee_id_number',
    'department',
    'laptop_type',
    'checked_in_at',
    'checked_out_at',
                      ];
}
