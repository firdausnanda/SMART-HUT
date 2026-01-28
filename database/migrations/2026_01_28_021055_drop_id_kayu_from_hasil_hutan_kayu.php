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
            if (Schema::hasColumn('hasil_hutan_kayu', 'id_kayu')) {
                $table->dropColumn('id_kayu');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
            //
        });
    }
};
