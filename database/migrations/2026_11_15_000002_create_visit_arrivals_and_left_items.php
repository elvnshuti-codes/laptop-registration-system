<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How the visitor arrived: one row per visit.
        Schema::create('visit_arrivals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_visit_id')->unique()->constrained('visitor_visits')->cascadeOnDelete();
            $table->string('mode', 20);                      // vehicle, motorcycle, bicycle, foot
            $table->string('number_plate', 40)->nullable();
            $table->timestamps();
        });

        // What the visitor left at the gate: one row per item, with the officer who took it.
        Schema::create('visit_left_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_visit_id')->constrained('visitor_visits')->cascadeOnDelete();
            $table->string('item_name', 120);
            $table->string('colour', 60);
            $table->unsignedSmallInteger('quantity');
            $table->string('notes', 255)->nullable();
            $table->foreignId('handled_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_left_items');
        Schema::dropIfExists('visit_arrivals');
    }
};