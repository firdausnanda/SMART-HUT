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
        Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
            if (!Schema::hasColumn('hasil_hutan_kayu', 'pengelola_hutan_id')) {
                $table->unsignedBigInteger('pengelola_hutan_id')->nullable()->after('regency_id');
                $table->index('pengelola_hutan_id');
            }
            if (Schema::hasColumn('hasil_hutan_kayu', 'district_id')) {
                $table->dropColumn('district_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
            $table->unsignedBigInteger('district_id')->after('regency_id')->nullable(); // Re-add district_id
            $table->dropColumn('pengelola_hutan_id');
        });
    }
};
