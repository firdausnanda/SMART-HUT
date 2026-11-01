<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Klien integrasi adalah sistem eksternal (mis. SIPETAK, CDK Trenggalek)
     * yang membaca data lewat /api/v1 memakai token Sanctum.
     */
    public function up(): void
    {
        Schema::create('integration_clients', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            // Null = seluruh provinsi; terisi = data dibatasi ke CDK ini.
            $table->foreignId('cdk_id')->nullable()->constrained('cdks')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_clients');
    }
};
