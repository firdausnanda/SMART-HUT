<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('regency_id', 4)->nullable()->index();
        });

        Schema::create('penanaman_targets', function (Blueprint $table) {
            $table->id();
            $table->char('regency_id', 4);
            $table->unsignedSmallInteger('year');
            $table->decimal('target_ha', 15, 2);
            $table->unsignedBigInteger('target_batang');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['regency_id', 'year']);
        });

        Schema::create('penanaman', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->char('regency_id', 4);
            $table->char('district_id', 7)->nullable();
            $table->char('village_id', 10)->nullable();
            $table->string('location_name')->nullable();
            $table->string('coordinates')->nullable();
            $table->decimal('realization_ha', 15, 2)->nullable();
            $table->string('status', 10)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['regency_id', 'year', 'month', 'status']);
        });

        Schema::create('penanaman_species', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penanaman_id')->constrained('penanaman')->cascadeOnDelete();
            $table->string('species_name')->nullable();
            $table->unsignedBigInteger('batang')->nullable();
            $table->timestamps();
        });

        Schema::create('penanaman_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penanaman_id')->constrained('penanaman')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20);
            $table->text('note');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penanaman_events');
        Schema::dropIfExists('penanaman_species');
        Schema::dropIfExists('penanaman');
        Schema::dropIfExists('penanaman_targets');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('regency_id'));
    }
};
