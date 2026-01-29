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
        // 1. Add volume_target to parent
        Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
            $table->decimal('volume_target', 15, 2)->after('forest_type')->default(0);
        });

        // 2. Migrate data: Sum volume_target from details and update parent
        // Using raw SQL for performance and simplicity in migration
        DB::statement("
            UPDATE hasil_hutan_kayu
            SET volume_target = (
                SELECT COALESCE(SUM(volume_target), 0)
                FROM hasil_hutan_kayu_details
                WHERE hasil_hutan_kayu_details.hasil_hutan_kayu_id = hasil_hutan_kayu.id
            )
        ");

        // 3. Drop old columns
        Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
            if (Schema::hasColumn('hasil_hutan_kayu', 'annual_volume_target')) {
                $table->dropColumn('annual_volume_target');
            }
        });

        Schema::table('hasil_hutan_kayu_details', function (Blueprint $table) {
            if (Schema::hasColumn('hasil_hutan_kayu_details', 'volume_target')) {
                $table->dropColumn('volume_target');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Restore columns
        Schema::table('hasil_hutan_kayu_details', function (Blueprint $table) {
            $table->decimal('volume_target', 15, 2)->default(0);
        });

        Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
            $table->string('annual_volume_target')->nullable();
            $table->dropColumn('volume_target');
        });

        // Reverting data is tricky because we lost granular target info.
        // We can't simply move it back. We just restored the column with 0 default.
    }
};
