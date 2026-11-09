<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_purposes', function (Blueprint $table) {
            $table->boolean('is_testing')->default(false)->after('is_system');
        });

        // The Testing purpose: visitors who bring samples. Admin can rename it later.
        DB::table('visit_purposes')->updateOrInsert(
            ['name' => 'Testing'],
            ['is_system' => false, 'is_testing' => true, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('visit_purposes')->where('name', 'Testing')->delete();

        Schema::table('visit_purposes', function (Blueprint $table) {
            $table->dropColumn('is_testing');
        });
    }
};