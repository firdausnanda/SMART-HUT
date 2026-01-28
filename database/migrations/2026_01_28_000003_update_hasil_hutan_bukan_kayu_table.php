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
      if (Schema::hasColumn('hasil_hutan_bukan_kayu', 'id_bukan_kayu')) {
        $table->dropColumn('id_bukan_kayu');
      }
      if (Schema::hasColumn('hasil_hutan_bukan_kayu', 'annual_volume_target')) {
        $table->dropColumn('annual_volume_target');
      }
      if (Schema::hasColumn('hasil_hutan_bukan_kayu', 'annual_volume_realization')) {
        $table->dropColumn('annual_volume_realization');
      }
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('hasil_hutan_bukan_kayu', function (Blueprint $table) {
      $table->string('id_bukan_kayu')->nullable(); // Revert to nullable string as it was likely not a pure FK initially or mixed
      $table->string('annual_volume_target')->nullable();
      $table->string('annual_volume_realization')->nullable();
    });
  }
};
