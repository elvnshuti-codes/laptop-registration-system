<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sample_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_visit_id')->unique()->constrained('visitor_visits')->cascadeOnDelete();
            $table->foreignId('served_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_number', 50)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('served_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_receptions');
    }
};