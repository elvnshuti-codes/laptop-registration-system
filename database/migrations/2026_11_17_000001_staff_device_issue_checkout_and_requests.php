<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // Devices handed to an employee during the day, before they were ever checked in.
        Schema::table('staff_devices', function (Blueprint $table) {
            $table->timestamp('issued_at')->nullable();                 // when it was issued (sticker printed)
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at')->nullable();              // when the owner collected it
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issue_closed_at')->nullable();           // first check-out (or Admin approval): no longer "issued"
        });

        // Requests from Entry to Admin: a device came back without having been checked out.
        Schema::create('staff_device_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);                                 // stale_entry | issued_device
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('staff_entry_id')->nullable()->constrained('staff_entries')->nullOnDelete();
            $table->foreignId('staff_device_id')->nullable()->constrained('staff_devices')->nullOnDelete();
            $table->json('device_ids')->nullable();                     // UNBS devices the request covers
            $table->text('details');                                    // what the officer saw, written down at the time
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 12)->default('pending');           // pending | approved | declined | used | expired
            // E<entry id> or D<device id> while the request is open: the unique index allows only one open request each.
            $table->string('open_key', 30)->nullable()->unique();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 255)->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('released_at')->nullable();               // declined: the held device was handed over
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('staff_entries', function (Blueprint $table) {
            $table->string('close_reason', 30)->nullable();             // admin_override | auto_personal
            $table->foreignId('closed_by_admin')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('staff_entry_devices', function (Blueprint $table) {
            $table->string('close_reason', 30)->nullable();
            $table->foreignId('admin_request_id')->nullable()->constrained('staff_device_requests')->nullOnDelete();
        });

        // Issued devices that leave without ever having been checked in.
        Schema::create('staff_issue_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_device_id')->constrained('staff_devices')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->timestamp('issued_at');
            $table->timestamp('collected_at')->nullable();
            $table->string('location', 20)->default('entry');
            $table->foreignId('checked_out_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('checked_out_at');
            $table->timestamps();
        });

        // Admin always has it; it can be given to others on the User access screen.
        Permission::findOrCreate('approve device requests', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_issue_checkouts');

        Schema::table('staff_entry_devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_request_id');
            $table->dropColumn('close_reason');
        });

        Schema::table('staff_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by_admin');
            $table->dropColumn('close_reason');
        });

        Schema::dropIfExists('staff_device_requests');

        Schema::table('staff_devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issued_by');
            $table->dropConstrainedForeignId('collected_by');
            $table->dropColumn(['issued_at', 'collected_at', 'issue_closed_at']);
        });

        Permission::where('name', 'approve device requests')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};