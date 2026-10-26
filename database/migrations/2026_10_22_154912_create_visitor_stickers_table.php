<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_stickers', function (Blueprint $table) {
            $table->id();
            $table->string('sticker_code')->unique(); // e.g. UNBS-VS-0001
            $table->timestamps();                     // created_at = when the sticker was generated
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_stickers');
    }
};