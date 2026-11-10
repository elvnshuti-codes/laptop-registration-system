<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unserved_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('requires_note')->default(false);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $reasons = [
            ['Long queue at Reception', false, false],
            ['Urgent commitment elsewhere', false, false],
            ['Emergency', false, false],
            ['Reception officer was not available', false, false],
            ['Visitor changed their mind', false, false],
            ['Other', true, true],
        ];

        foreach ($reasons as [$name, $requiresNote, $isSystem]) {
            DB::table('unserved_reasons')->insert([
                'name' => $name,
                'requires_note' => $requiresNote,
                'is_system' => $isSystem,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unserved_reasons');
    }
};