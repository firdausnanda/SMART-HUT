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
        Schema::create('nilai_ekonomi_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nilai_ekonomi_id')->constrained('nilai_ekonomi')->onDelete('cascade');
            $table->foreignId('commodity_id')->constrained('m_commodities');
            $table->decimal('production_volume', 15, 2);
            $table->string('satuan')->default('Kg');
            $table->decimal('transaction_value', 19, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai_ekonomi_details');
    }
};
