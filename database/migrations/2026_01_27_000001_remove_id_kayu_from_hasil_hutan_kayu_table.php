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
    // Drop id_kayu from hasil_hutan_kayu
    Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
      if (Schema::hasColumn('hasil_hutan_kayu', 'id_kayu')) {
        $table->dropColumn('id_kayu');
      }
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('hasil_hutan_kayu', function (Blueprint $table) {
      // Restore id_kayu, strictly speaking we can't restore data but can restore column
      $table->string('id_kayu')->nullable();
    });
  }
};
