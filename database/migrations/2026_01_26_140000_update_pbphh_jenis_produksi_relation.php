<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    if (!Schema::hasTable('pbphh_jenis_produksi')) {
      Schema::create('pbphh_jenis_produksi', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('pbphh_id');
        $table->unsignedBigInteger('jenis_produksi_id');
        $table->string('kapasitas_ijin')->nullable(); // Request asks for 'kapasitas_ijin'
        $table->timestamps();

        $table->foreign('pbphh_id')->references('id')->on('pbphh')->onDelete('cascade');
        $table->foreign('jenis_produksi_id')->references('id')->on('m_jenis_produksi')->onDelete('cascade');
      });

      // Migrate existing data only if table was just created or we want to ensure migration
      // Ideally only if we just created it or if we check first.
      // Assuming if table exists, data is there.
      $existing = DB::table('pbphh')->select('id', 'id_jenis_produksi')->whereNotNull('id_jenis_produksi')->get();
      foreach ($existing as $item) {
        DB::table('pbphh_jenis_produksi')->insert([
          'pbphh_id' => $item->id,
          'jenis_produksi_id' => $item->id_jenis_produksi,
          'kapasitas_ijin' => '0', // Default value or null
          'created_at' => now(),
          'updated_at' => now(),
        ]);
      }
    }

    if (Schema::hasColumn('pbphh', 'id_jenis_produksi')) {
      Schema::table('pbphh', function (Blueprint $table) {
        $table->dropColumn('id_jenis_produksi');
      });
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('pbphh', function (Blueprint $table) {
      $table->unsignedBigInteger('id_jenis_produksi')->nullable();
    });

    // Restore data (approximation, takes the first one found)
    $relations = DB::table('pbphh_jenis_produksi')->get();
    foreach ($relations as $rel) {
      DB::table('pbphh')->where('id', $rel->pbphh_id)->update(['id_jenis_produksi' => $rel->jenis_produksi_id]);
    }

    Schema::dropIfExists('pbphh_jenis_produksi');
  }
};
