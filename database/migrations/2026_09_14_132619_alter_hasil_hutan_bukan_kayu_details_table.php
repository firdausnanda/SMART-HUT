<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clean up data before altering column type
        \Illuminate\Support\Facades\DB::table('hasil_hutan_bukan_kayu_details')
            ->where('annual_volume_realization', '')
            ->orWhereNull('annual_volume_realization')
            ->update(['annual_volume_realization' => '0']);

        \Illuminate\Support\Facades\DB::statement("UPDATE hasil_hutan_bukan_kayu_details SET annual_volume_realization = REPLACE(annual_volume_realization, ',', '.')");
        
        // Remove any other non-numeric characters (simple approach: set to 0 if not numeric)
        \Illuminate\Support\Facades\DB::statement("UPDATE hasil_hutan_bukan_kayu_details SET annual_volume_realization = '0' WHERE annual_volume_realization NOT REGEXP '^[0-9]+(\\\\.[0-9]+)?$'");

        Schema::table('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE hasil_hutan_bukan_kayu_details MODIFY annual_volume_realization DECIMAL(10, 2) NOT NULL DEFAULT 0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE hasil_hutan_bukan_kayu_details MODIFY annual_volume_realization VARCHAR(255) NOT NULL DEFAULT '0'");
        });
    }
};
