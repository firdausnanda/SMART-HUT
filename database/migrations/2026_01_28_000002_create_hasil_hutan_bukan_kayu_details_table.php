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
    Schema::create('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('hasil_hutan_bukan_kayu_id');
      $table->unsignedBigInteger('commodity_id');
      $table->string('volume'); // Using string to match original annual_volume_target type if it was string, or decimal if needed. Plan said Decimal/Double but original was string. I'll use decimal for better calcs, but sticking to plan.
      $table->string('unit')->default('Kg'); // Default unit
      $table->timestamps();

      $table->foreign('hasil_hutan_bukan_kayu_id')->references('id')->on('hasil_hutan_bukan_kayu')->onDelete('cascade');
      $table->foreign('commodity_id')->references('id')->on('m_commodities')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('hasil_hutan_bukan_kayu_details');
  }
};
