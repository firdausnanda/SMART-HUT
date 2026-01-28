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
    Schema::table('hasil_hutan_kayu_details', function (Blueprint $table) {
      if (!Schema::hasColumn('hasil_hutan_kayu_details', 'volume_target')) {
        $table->decimal('volume_target', 15, 2)->default(0)->after('kayu_id');
      }
      if (!Schema::hasColumn('hasil_hutan_kayu_details', 'volume_realization')) {
        $table->decimal('volume_realization', 15, 2)->default(0)->after('volume_target');
      }
    });

    Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
      if (Schema::hasColumn('hasil_hutan_kayu', 'annual_volume_target')) {
        $table->dropColumn('annual_volume_target');
      }
      if (Schema::hasColumn('hasil_hutan_kayu', 'annual_volume_realization')) {
        $table->dropColumn('annual_volume_realization');
      }
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
      $table->string('annual_volume_target');
      $table->string('annual_volume_realization');
    });

    Schema::table('hasil_hutan_kayu_details', function (Blueprint $table) {
      $table->dropColumn(['volume_target', 'volume_realization']);
    });
  }
};
