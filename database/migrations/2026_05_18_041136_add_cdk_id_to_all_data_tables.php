<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $tables = [
        'rehab_lahan',
        'penghijauan_lingkungan',
        'rehab_manggrove',
        'rhl_teknis',
        'reboisasi_ps',
        'kebakaran_hutan',
        'pengunjung_wisata',
        'hasil_hutan_kayu',
        'hasil_hutan_bukan_kayu',
        'pbphh',
        'realisasi_pnbp',
        'skps',
        'kups',
        'nilai_ekonomi',
        'nilai_transaksi_ekonomi',
        'perkembangan_kth',
        'pegawais',
        'bezettings',
        'rekap_bulanan_pegawai',
        'rekap_statistik_bulanan',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'cdk_id')) {
                        $table->foreignId('cdk_id')->nullable()->constrained('cdks')->nullOnDelete();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'cdk_id')) {
                        $table->dropForeign(['cdk_id']);
                        $table->dropColumn('cdk_id');
                    }
                });
            }
        }
    }
};
