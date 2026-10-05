<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laptop extends Model
{
    protected $fillable = [ 'asset_tag', 'type', 'department']; 
    
    public function registrations()
    {
        return $this->hasMany(LaptopRegistration::class);
    }

}
