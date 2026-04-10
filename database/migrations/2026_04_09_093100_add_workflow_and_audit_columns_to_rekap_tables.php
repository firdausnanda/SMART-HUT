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
        $tables = ['rekap_statistik_bulanan', 'rekap_bulanan_pegawai'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('status')->default('draft')->after('periode_bulan');
                $table->timestamp('approved_by_kasi_at')->nullable()->after('status');
                $table->timestamp('approved_by_cdk_at')->nullable()->after('approved_by_kasi_at');
                $table->text('rejection_note')->nullable()->after('approved_by_cdk_at');
                
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['rekap_statistik_bulanan', 'rekap_bulanan_pegawai'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropForeign(['updated_by']);
                $table->dropForeign(['deleted_by']);
                
                $table->dropColumn([
                    'status',
                    'approved_by_kasi_at',
                    'approved_by_cdk_at',
                    'rejection_note',
                    'created_by',
                    'updated_by',
                    'deleted_by',
                    'deleted_at'
                ]);
            });
        }
    }
};
