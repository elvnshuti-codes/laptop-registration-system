<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->string('name');
            $table->string('visitor_mobile_number', 20)->nullable();
            $table->string('purpose');
            $table->string('purpose_other')->nullable();
            $table->string('id_document_type');
            $table->string('id_document_other')->nullable();
            $table->string('id_document_number', 50);
            $table->string('meeting_with');
            $table->date('expected_date');
            $table->time('expected_time')->nullable();
            $table->text('device_note')->nullable();
            $table->string('status')->default('expected');
            $table->timestamp('arrived_at')->nullable();
            $table->foreignId('visitor_visit_id')->nullable()->constrained('visitor_visits')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['expected_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_bookings');
    }
};
