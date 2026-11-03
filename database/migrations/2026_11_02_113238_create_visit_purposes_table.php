<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_purposes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Starting list. "Other" is built in: the forms use it to ask for a description.
        $now = now();
        DB::table('visit_purposes')->insert([
            ['name' => 'Meeting',   'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Delivery',  'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Interview', 'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Other',     'is_system' => true,  'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_purposes');
    }
};