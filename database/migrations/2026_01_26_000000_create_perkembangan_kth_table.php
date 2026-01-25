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
    Schema::create('perkembangan_kth', function (Blueprint $table) {
      $table->id();
      $table->year('year');
      $table->tinyInteger('month'); // 1-12

      // Location
      $table->foreignId('province_id')->nullable();
      $table->foreignId('regency_id')->nullable();
      $table->foreignId('district_id')->nullable();
      $table->foreignId('village_id')->nullable();

      // KTH Data
      $table->string('nama_kth');
      $table->string('nomor_register')->nullable();
      $table->enum('kelas_kelembagaan', ['pemula', 'madya', 'utama']);
      $table->integer('jumlah_anggota')->default(0);
      $table->decimal('luas_kelola', 15, 2)->default(0); // Ha
      $table->text('potensi_kawasan')->nullable();

      // Status & Approval
      $table->string('status')->default('draft'); // draft, waiting_kasi, waiting_cdk, final, rejected
      $table->timestamp('approved_by_kasi_at')->nullable();
      $table->timestamp('approved_by_cdk_at')->nullable();
      $table->text('rejection_note')->nullable();

      // Meta
      $table->unsignedBigInteger('created_by')->nullable();
      $table->unsignedBigInteger('updated_by')->nullable();
      $table->unsignedBigInteger('deleted_by')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('perkembangan_kth');
  }
};
