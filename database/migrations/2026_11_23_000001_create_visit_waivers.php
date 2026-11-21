<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A step the Gate skipped on purpose, with who allowed it. Never blocks a visit.
        Schema::create('visit_waivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_visit_id')->constrained('visitor_visits')->cascadeOnDelete();
            $table->string('step', 20);                       // id_document | arms | vip_details
            $table->string('reason', 80);
            $table->string('note', 300)->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamp('approved_at')->nullable();     // Supervisor review, never blocking
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('approved_after_exit')->default(false);
            $table->timestamps();

            $table->index(['visitor_visit_id', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_waivers');
    }
};