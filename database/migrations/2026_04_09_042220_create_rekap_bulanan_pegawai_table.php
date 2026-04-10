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
        Schema::create('rekap_bulanan_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawais')->cascadeOnDelete();
            $table->integer('periode_tahun');
            $table->integer('periode_bulan');
            $table->string('nip');
            $table->string('nama_lengkap');
            $table->string('status_pegawai');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('pendidikan_terakhir');
            $table->string('pangkat_golongan')->nullable();
            $table->foreignId('bezetting_id')->nullable()->constrained('bezettings')->nullOnDelete();
            $table->string('nama_jabatan_snapshot')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->string('skpd')->nullable();
            $table->string('status_kedudukan')->nullable();
            $table->string('status_pernikahan')->nullable();
            $table->string('generasi')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->integer('usia_per_periode')->nullable();
            $table->date('tmt_cpns')->nullable();
            $table->date('tmt_pns')->nullable();
            $table->integer('masa_kerja_tahun')->nullable();
            $table->integer('bup')->nullable();
            $table->date('proyeksi_pensiun')->nullable();
            $table->integer('bulan_pensiun_tersisa')->nullable();
            $table->bigInteger('gaji_pokok_terakhir')->nullable();
            $table->date('tmt_kgb_berikutnya')->nullable();
            $table->string('sumber_data')->default('manual');
            $table->timestamps();

            $table->unique(['pegawai_id', 'periode_tahun', 'periode_bulan'], 'rekap_pegawai_periode_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_bulanan_pegawai');
    }
};
