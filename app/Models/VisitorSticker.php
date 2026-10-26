<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorSticker extends Model
{
    // Only the code is ever set by us; id and timestamps are automatic.
    // Table name 'visitor_stickers' is guessed from the class name.
    protected $fillable = ['sticker_code'];
}