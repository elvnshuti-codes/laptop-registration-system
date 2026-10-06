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
        Schema::create('visitor_visits', function (Blueprint $table) {
        $table->id();
        $table->string('visit_code')->unique();
        $table->string('name');
        $table->string('purpose');
        $table->string('purpose_other')->nullable();
        $table->string('id_document_type');
        $table->string('id_document_other')->nullable();
        $table->string('visitor_card_number');
        $table->timestamp('gate_checked_in_at');
        $table->timestamp('gate_checked_out_at')->nullable();
        $table->timestamp('no_device_confirmed_at')->nullable();
        $table->timestamp('document_returned_at')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_visits');
    }
};
