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
            $table->foreignId('pengelola_wisata_id')->nullable()->after('pengelola_hutan_id')->constrained('m_pengelola_wisata')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
            $table->dropForeign(['pengelola_wisata_id']);
            $table->dropColumn('pengelola_wisata_id');
        });
    }
};
