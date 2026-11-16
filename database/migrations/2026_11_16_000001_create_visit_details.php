<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What a visitor came for, when the purpose is not Testing or Security.
        Schema::create('visit_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_visit_id')->unique()->constrained('visitor_visits')->cascadeOnDelete();
            $table->string('department', 120)->nullable();
            $table->string('person_to_see')->nullable();
            $table->string('details', 500);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_details');
    }
};