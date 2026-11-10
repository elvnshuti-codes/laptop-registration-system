<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_visit_id')->constrained('visitor_visits')->cascadeOnDelete();
            // sent_back | left_unserved | skipped_entry
            $table->string('type', 20)->index();
            // Which reception was responsible: Standard House or Sample House.
            $table->string('area', 20);
            $table->string('reason', 100)->nullable();
            $table->text('note')->nullable();
            $table->boolean('phone_confirmed')->default(false);
            $table->string('confirmed_with', 100)->nullable();
            // The stage the visitor had reached when this was recorded.
            $table->string('last_stage', 30)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_exceptions');
    }
};