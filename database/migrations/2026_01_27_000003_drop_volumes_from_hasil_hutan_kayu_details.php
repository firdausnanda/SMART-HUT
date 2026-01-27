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
      $table->dropColumn(['volume_target', 'volume_realization']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('hasil_hutan_kayu_details', function (Blueprint $table) {
      $table->decimal('volume_target', 15, 2)->default(0);
      $table->decimal('volume_realization', 15, 2)->default(0);
    });
  }
};
