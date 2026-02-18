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
    Schema::table('reboisasi_ps', function (Blueprint $table) {
      $table->unsignedBigInteger('pengelola_id')->nullable()->after('village_id');
      $table->foreign('pengelola_id')->references('id')->on('m_pengelola_ps')->nullOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('reboisasi_ps', function (Blueprint $table) {
      $table->dropForeign(['pengelola_id']);
      $table->dropColumn('pengelola_id');
    });
  }
};
