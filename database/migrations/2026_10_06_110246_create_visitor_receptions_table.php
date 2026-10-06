<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visitor_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_visit_id')->constrained('visitor_visits')->cascadeOnDelete();
            $table->string('destination');
            $table->string('meeting_with')->nullable();
            $table->string('purpose_confirmed');
            $table->boolean('served')->default(false);
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_receptions');
    }
};
