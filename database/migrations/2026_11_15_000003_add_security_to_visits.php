<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A purpose can now be flagged as "Security" (uniformed officers on duty).
        Schema::table('visit_purposes', function (Blueprint $table) {
            $table->boolean('is_security')->default(false)->after('name');
        });

        // Add the Security purpose, or flag it if it already exists.
        $existing = DB::table('visit_purposes')->where('name', 'Security')->first();

        if ($existing) {
            DB::table('visit_purposes')->where('id', $existing->id)->update(['is_security' => true]);
        } else {
            DB::table('visit_purposes')->insert([
                'name' => 'Security',
                'is_system' => false,
                'is_testing' => false,
                'is_security' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // One row per Security visit: who the officer is and who they protect.
        Schema::create('visit_security_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_visit_id')->unique()->constrained('visitor_visits')->cascadeOnDelete();
            $table->string('force', 10);                    // UPDF or UPF
            $table->string('officer_rank', 80);
            $table->string('vip_name');
            $table->string('vip_title');
            $table->string('locker_number', 40)->nullable();
            $table->foreignId('handled_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        // The arms the officer leaves at the gate: one row per weapon.
        Schema::create('visit_arms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_visit_id')->constrained('visitor_visits')->cascadeOnDelete();
            $table->string('arm_type', 40);
            $table->string('make_model', 120)->nullable();
            $table->string('serial_number', 60);
            $table->unsignedSmallInteger('magazines')->nullable();
            $table->unsignedSmallInteger('rounds');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_arms');
        Schema::dropIfExists('visit_security_details');
        Schema::table('visit_purposes', function (Blueprint $table) {
            $table->dropColumn('is_security');
        });
    }
};