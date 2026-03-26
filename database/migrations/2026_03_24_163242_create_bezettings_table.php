<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('bezettings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jabatan'); // ex: Polisi Kehutanan Ahli Pertama
            $table->integer('kebutuhan'); // Kuota ideal / ABK

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
        Schema::dropIfExists('bezettings');
    }
};