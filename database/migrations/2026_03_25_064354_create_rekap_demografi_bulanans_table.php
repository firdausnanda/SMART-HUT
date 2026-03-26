<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('rekap_demografi_bulanans', function (Blueprint $table) {
            $table->id();
            $table->integer('periode_tahun'); // Contoh: 2026
            $table->integer('periode_bulan'); // Contoh: 3 (untuk Maret)

            $table->integer('total_pegawai_aktif');

            // Kolom JSON untuk mempermudah visualisasi chart di React
            $table->json('statistik_status_pegawai'); // {"PNS": 30, "PPPK": 15, "Honorer": 5}
            $table->json('statistik_pendidikan'); // {"SMA": 5, "D3": 10, "S1": 30, "S2": 5}
            $table->json('statistik_golongan'); // {"III/a": 10, "III/b": 5, ...}
            $table->json('statistik_jenis_kelamin'); // {"L": 30, "P": 20}
            $table->json('statistik_generasi')->nullable(); // Contoh isi: {"Gen X": 10, "Milenial": 25, "Gen Z": 5}
            $table->json('statistik_status_pernikahan')->nullable(); // Contoh isi: {"Kawin": 10, "Belum Kawin": 25, "Cerai": 5}

            $table->timestamps();

            // Mencegah duplikasi data rekap di bulan dan tahun yang sama
            $table->unique(['periode_tahun', 'periode_bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_demografi_bulanans');
    }
};
