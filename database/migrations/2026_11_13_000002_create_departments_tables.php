<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->timestamps();
        });

        // Other ways a department is written (NML, F&A, ICT...). The key is the name's words, lowercase:
        // "F&A", "f a" and "F. A" all share the key "f a". No department means "ignore this text".
        Schema::create('department_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('alias_key', 120)->unique();
            $table->foreignId('department_id')->nullable()->constrained('departments')->cascadeOnDelete();
            $table->timestamps();
        });

        $now = now();

        $departments = [
            'Audit', 'Certification', 'Compliance', "ED's Office", 'Finance and Administration', 'Human Resource',
            'Imports Inspection', 'Information and Communication Technology', 'Legal', 'Legal Metrology',
            'Management and Financial Services', 'Market Surveillance', 'Marketing and Public Relations',
            'National Metrology Laboratories', 'Standards', 'Testing',
        ];

        foreach ($departments as $name) {
            DB::table('departments')->insert(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }

        // Spellings that clearly mean one department. Anything not sure is asked about in the import preview.
        $aliases = [
            'Certification' => ['Certifcation'],
            'Imports Inspection' => ['Import Inspection', 'Imports', 'Import'],
            'Standards' => ['Standards Directorate'],
            'Market Surveillance' => ['Surveillance'],
            'National Metrology Laboratories' => ['NML'],
            'Legal Metrology' => ['LMD'],
            'Finance and Administration' => ['F&A', 'F.A.D', 'FAD', 'Fin & Admin', 'Stores (F&A)'],
            'Audit' => ['Internal Audit'],
            'Information and Communication Technology' => ['ICT'],
            'Legal' => ['Legal Counsel'],
            'Marketing and Public Relations' => ['PRM', 'PR', 'Public Relations'],
            'Human Resource' => ['HR', 'HRD', 'Human Resources'],
            'Management and Financial Services' => ['MFS'],
            "ED's Office" => ["ED's Department"],
        ];

        $key = fn (string $text) => implode(' ', preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY));

        foreach ($aliases as $name => $spellings) {
            $departmentId = DB::table('departments')->where('name', $name)->value('id');

            foreach ($spellings as $spelling) {
                DB::table('department_aliases')->insert([
                    'alias_key' => $key($spelling),
                    'department_id' => $departmentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('department_aliases');
        Schema::dropIfExists('departments');
    }
};