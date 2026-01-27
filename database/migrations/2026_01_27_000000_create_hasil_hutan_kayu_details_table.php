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
    Schema::create('hasil_hutan_kayu_details', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('hasil_hutan_kayu_id');
      $table->unsignedBigInteger('kayu_id'); // foreign key to m_kayu
      $table->decimal('volume_target', 15, 2);
      $table->decimal('volume_realization', 15, 2);
      $table->timestamps();

      $table->foreign('hasil_hutan_kayu_id')->references('id')->on('hasil_hutan_kayu')->onDelete('cascade');
      $table->foreign('kayu_id')->references('id')->on('m_kayu')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('hasil_hutan_kayu_details');
  }
};
