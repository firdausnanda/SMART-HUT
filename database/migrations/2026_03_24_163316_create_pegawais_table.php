<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique();
            $table->string('nama_lengkap');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('agama');
            $table->string('pendidikan_terakhir');
            $table->string('status_pegawai');

            // Relasi ke tabel bezettings
            $table->foreignId('bezetting_id')->nullable()->constrained('bezettings')->nullOnDelete();

            $table->string('pangkat_golongan')->nullable();
            $table->date('tmt_cpns')->nullable();
            $table->integer('bup');

            $table->string('status_kedudukan')->default('Aktif');

            $table->string('nik')->nullable()->unique();
            $table->string('status_pernikahan')->nullable();
            $table->text('alamat')->nullable();
            $table->date('tmt_pns')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->string('skpd')->nullable();

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
        Schema::dropIfExists('pegawais');
    }
};