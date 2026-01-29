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
            $table->unsignedBigInteger('pengelola_hutan_id')->nullable()->after('district_id');
            $table->foreign('pengelola_hutan_id')->references('id')->on('m_pengelola_hutan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hasil_hutan_bukan_kayu', function (Blueprint $table) {
            $table->dropForeign(['pengelola_hutan_id']);
            $table->dropColumn('pengelola_hutan_id');
        });
    }
};
