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
        Schema::table('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
            $table->renameColumn('commodity_id', 'bukan_kayu_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
            $table->renameColumn('bukan_kayu_id', 'commodity_id');
        });
    }
};
