<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean existing data
        DB::table('pbphh_jenis_produksi')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $capacity = $row->kapasitas_ijin;
                if ($capacity !== null && $capacity !== '') {
                    // Remove 'm3' or 'm³' before parsing numbers to prevent '3' from being absorbed
                    $cleanCapacity = str_ireplace(['m3', 'm³'], '', $capacity);
                    $clean = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $cleanCapacity));
                    $cleanValue = $clean === '' ? 0 : floatval($clean);
                    
                    DB::table('pbphh_jenis_produksi')
                        ->where('id', $row->id)
                        ->update(['kapasitas_ijin' => $cleanValue]);
                }
            }
        });

        // 2. Change column type using raw SQL to avoid requiring doctrine/dbal package
        DB::statement('ALTER TABLE pbphh_jenis_produksi MODIFY kapasitas_ijin DECIMAL(15, 2) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE pbphh_jenis_produksi MODIFY kapasitas_ijin VARCHAR(255) NULL');
    }
};
