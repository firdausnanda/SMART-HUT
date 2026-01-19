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
        Schema::create('pengunjung_wisata', function (Blueprint $table) {
            $table->id();

            $table->year('year');
            $table->tinyInteger('month');

            $table->string('id_pengelola_wisata');
            $table->string('number_of_visitors');
            $table->string('gross_income');

            // Status & Approval
            $table->string('status')->default('draft'); // draft, waiting_kasi, waiting_cdk, finalized, rejected
            $table->timestamp('approved_by_kasi_at')->nullable();
            $table->timestamp('approved_by_cdk_at')->nullable();
            $table->text('rejection_note')->nullable(); // Alasan tolak

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
        Schema::dropIfExists('pengunjung_wisata');
    }
};
