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
            $table->foreignId('district_id')->nullable()->after('regency_id')->constrained('m_districts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropColumn('district_id');
        });
    }
};
