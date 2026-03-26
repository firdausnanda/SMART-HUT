<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('riwayat_kgbs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawais')->cascadeOnDelete();
            $table->string('no_sk');
            $table->date('tanggal_sk');
            $table->date('tmt_kgb');
            $table->bigInteger('gaji_pokok_baru');
            $table->date('tmt_kgb_berikutnya'); // Otomatis tmt_kgb + 2 tahun

            // Status verifikasi berjenjang
            $table->enum('status', ['draft', 'waiting_kasi', 'waiting_cdk', 'final', 'rejected'])->default('draft');
            $table->timestamp('approved_by_kasi_at')->nullable();
            $table->timestamp('approved_by_cdk_at')->nullable();
            $table->text('rejection_note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kgbs');
    }
};
