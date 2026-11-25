<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penanaman', function (Blueprint $table) {
            $table->string('fund_source')->nullable()->after('realization_ha');
        });
    }

    public function down(): void
    {
        Schema::table('penanaman', fn (Blueprint $table) => $table->dropColumn('fund_source'));
    }
};
