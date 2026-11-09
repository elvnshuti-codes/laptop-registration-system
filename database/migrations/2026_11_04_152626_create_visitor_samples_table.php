<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_visit_id')->constrained('visitor_visits')->cascadeOnDelete();
            $table->string('sample_type', 100);
            $table->unsignedSmallInteger('sample_count');
            $table->timestamps();
        });

        // Keep any sample already recorded in the old single columns.
        DB::table('visitor_visits')->whereNotNull('sample_type')->get(['id', 'sample_type', 'sample_count'])
            ->each(fn ($v) => DB::table('visitor_samples')->insert([
                'visitor_visit_id' => $v->id,
                'sample_type' => $v->sample_type,
                'sample_count' => $v->sample_count ?? 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

        Schema::table('visitor_visits', function (Blueprint $table) {
            $table->dropColumn(['sample_type', 'sample_count']);
        });
    }

    public function down(): void
    {
        Schema::table('visitor_visits', function (Blueprint $table) {
            $table->string('sample_type', 100)->nullable();
            $table->unsignedSmallInteger('sample_count')->nullable();
        });

        Schema::dropIfExists('visitor_samples');
    }
};