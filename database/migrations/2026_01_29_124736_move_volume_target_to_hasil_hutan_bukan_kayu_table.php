<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hasil_hutan_bukan_kayu', function (Blueprint $table) {
            $table->decimal('volume_target', 15, 2)->default(0)->after('district_id');
        });

        // Migrate data
        $parents = DB::table('hasil_hutan_bukan_kayu')->get();
        foreach ($parents as $parent) {
            $totalTarget = DB::table('hasil_hutan_bukan_kayu_details')
                ->where('hasil_hutan_bukan_kayu_id', $parent->id)
                ->sum('volume');

            DB::table('hasil_hutan_bukan_kayu')
                ->where('id', $parent->id)
                ->update(['volume_target' => $totalTarget]);
        }

        Schema::table('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
            $table->dropColumn('volume');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
            $table->decimal('volume', 15, 2)->default(0)->after('commodity_id');
        });

        // Reverse migration (this is tricky as we lost the per-commodity target distribution)
        // We'll just leave it as 0 or put the total into the first detail if available.
        $parents = DB::table('hasil_hutan_bukan_kayu')->get();
        foreach ($parents as $parent) {
            $firstDetail = DB::table('hasil_hutan_bukan_kayu_details')
                ->where('hasil_hutan_bukan_kayu_id', $parent->id)
                ->first();

            if ($firstDetail) {
                DB::table('hasil_hutan_bukan_kayu_details')
                    ->where('id', $firstDetail->id)
                    ->update(['volume' => $parent->volume_target]);
            }
        }

        Schema::table('hasil_hutan_bukan_kayu', function (Blueprint $table) {
            $table->dropColumn('volume_target');
        });
    }
};
