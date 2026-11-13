<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Serial numbers become optional (UNBS laptops are identified by their asset tag).
        Schema::table('staff_devices', function (Blueprint $table) {
            $table->string('serial_number', 60)->nullable()->change();
        });

        $prefixes = [
            'organization' => ['Laptop' => 'UL', 'Tablet' => 'UT'],
            'personal' => ['Laptop' => 'PL', 'Tablet' => 'PT'],
        ];

        $taken = [];

        // Give every existing device a short barcode and send its sticker back to "not printed".
        foreach (DB::table('staff_devices')->orderBy('id')->get() as $device) {
            $prefix = $prefixes[$device->owner_type][$device->device_type] ?? 'SD';

            // UNBS devices: the last digits of the asset tag. Others: the device number.
            if ($device->owner_type === 'organization' && $device->asset_tag && preg_match('/(\d+)$/', $device->asset_tag, $m)) {
                $base = sprintf('%s-%05d', $prefix, (int) substr($m[1], -4));
            } else {
                $base = sprintf('%s-%05d', $prefix, $device->id);
            }

            // A code already given in this run gets a letter: UL-01365, UL-01365A, UL-01365B...
            $code = $base;
            foreach (range('A', 'Z') as $letter) {
                if (! isset($taken[$code])) {
                    break;
                }
                $code = $base . $letter;
            }
            $taken[$code] = true;

            DB::table('staff_devices')->where('id', $device->id)->update([
                'sticker_code' => $code,
                'sticker_printed_at' => null,
            ]);
        }
    }

    public function down(): void
    {
    }
};