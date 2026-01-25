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
        Schema::table('nilai_ekonomi', function (Blueprint $table) {
            $table->dropForeign(['commodity_id']);
            $table->dropColumn(['commodity_id', 'production_volume', 'transaction_value']);
            $table->decimal('total_transaction_value', 19, 2)->default(0)->after('nama_kelompok');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nilai_ekonomi', function (Blueprint $table) {
            $table->foreignId('commodity_id')->nullable()->constrained('m_commodities');
            $table->decimal('production_volume', 15, 2)->nullable();
            $table->bigInteger('transaction_value')->nullable();
            $table->dropColumn('total_transaction_value');
        });
    }
};
