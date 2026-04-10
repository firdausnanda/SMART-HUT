<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekap_statistik_bulanan', function (Blueprint $table) {
            $table->id();
            $table->integer('periode_tahun');
            $table->integer('periode_bulan');
            $table->integer('total_pegawai_aktif')->default(0);
            $table->integer('total_pns')->default(0);
            $table->integer('total_pppk')->default(0);
            $table->integer('total_honorer')->default(0);
            $table->integer('total_laki')->default(0);
            $table->integer('total_perempuan')->default(0);
            $table->integer('total_pensiun_tahun_ini')->default(0);
            $table->integer('total_pensiun_bulan_ini')->default(0);
            $table->integer('total_pensiun_6_bulan')->default(0);
            $table->json('statistik_pendidikan')->nullable();
            $table->json('statistik_golongan')->nullable();
            $table->json('statistik_generasi')->nullable();
            $table->json('statistik_status_pernikahan')->nullable();
            $table->json('statistik_bezetting')->nullable();
            $table->json('statistik_unit_kerja')->nullable();
            $table->json('statistik_masa_kerja')->nullable();
            $table->json('statistik_usia')->nullable();
            $table->integer('kgb_jatuh_bulan_ini')->default(0);
            $table->integer('kgb_jatuh_3_bulan')->default(0);
            $table->string('dihasilkan_dari')->default('manual');
            $table->timestamp('dihasilkan_pada')->nullable();
            $table->timestamps();

            $table->unique(['periode_tahun', 'periode_bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_statistik_bulanan');
    }
};
