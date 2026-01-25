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
        Schema::table('skps', function (Blueprint $table) {
            $table->string('nama_kelompok')->after('id_skema_perhutanan_sosial')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skps', function (Blueprint $table) {
            $table->dropColumn('nama_kelompok');
        });
    }
};
