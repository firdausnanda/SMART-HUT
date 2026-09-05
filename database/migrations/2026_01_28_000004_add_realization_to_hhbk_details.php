<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    if (!Schema::hasColumn('hasil_hutan_bukan_kayu_details', 'annual_volume_realization')) {
      Schema::table('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
        $table->string('annual_volume_realization')->default('0')->after('volume');
      });
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
      $table->dropColumn('annual_volume_realization');
    });
  }
};
