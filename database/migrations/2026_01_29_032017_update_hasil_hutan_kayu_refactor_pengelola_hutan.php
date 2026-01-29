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
            $table->unsignedBigInteger('pengelola_hutan_id')->nullable()->after('regency_id');
            $table->dropColumn('district_id');

            // Optional: Add Index or Foreign Key if needed, though user didn't explicitly ask for constraint enforcing in DB, it's good practice.
            // But to avoid issues if table is empty or data mismatch, I'll keep it simple for now, or just index it.
            $table->index('pengelola_hutan_id');
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
