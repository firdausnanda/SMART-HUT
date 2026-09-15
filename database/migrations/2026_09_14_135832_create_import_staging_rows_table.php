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
        Schema::create('import_staging_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->integer('row_number');
            $table->json('data_payload');
            $table->enum('status', ['valid', 'invalid'])->default('valid');
            $table->json('validation_errors')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_staging_rows');
    }
};
