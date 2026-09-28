<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicDashboardQueryCountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'dashboard_testing', 'database.connections.dashboard_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'cache.default' => 'array']);
        DB::purge('dashboard_testing');
        Cache::flush();

        $tables = [
            'cdks' => ['nama', 'is_active'],
            'm_sumber_dana' => ['name'], 'm_regencies' => ['name'], 'm_pengelola_ps' => ['name'],
            'm_bangunan_kta' => ['name'], 'm_pengelola_wisata' => ['name'], 'm_kayu' => ['name'],
            'm_bukan_kayu' => ['name'], 'm_jenis_produksi' => ['name'],
            'm_skema_perhutanan_sosial' => ['name'], 'm_commodities' => ['name'],
            'rehab_lahan' => ['year', 'month', 'cdk_id', 'status', 'realization', 'target_annual', 'fund_source', 'regency_id'],
            'penghijauan_lingkungan' => ['year', 'month', 'cdk_id', 'status', 'realization', 'target_annual', 'fund_source', 'regency_id'],
            'rehab_manggrove' => ['year', 'month', 'cdk_id', 'status', 'realization', 'target_annual', 'fund_source', 'regency_id'],
            'reboisasi_ps' => ['year', 'month', 'cdk_id', 'status', 'realization', 'target_annual', 'fund_source', 'regency_id', 'pengelola_id'],
            'rhl_teknis' => ['year', 'month', 'cdk_id', 'status', 'target_annual', 'fund_source'],
            'rhl_teknis_details' => ['rhl_teknis_id', 'bangunan_kta_id', 'unit_amount'],
            'kebakaran_hutan' => ['year', 'month', 'cdk_id', 'status', 'number_of_fires', 'fire_area', 'id_pengelola_wisata'],
            'pengunjung_wisata' => ['year', 'month', 'cdk_id', 'status', 'number_of_visitors', 'gross_income', 'id_pengelola_wisata'],
            'hasil_hutan_kayu' => ['year', 'month', 'cdk_id', 'forest_type', 'status', 'volume_target'],
            'hasil_hutan_kayu_details' => ['hasil_hutan_kayu_id', 'kayu_id', 'volume_realization'],
            'hasil_hutan_bukan_kayu' => ['year', 'month', 'cdk_id', 'forest_type', 'status', 'volume_target'],
            'hasil_hutan_bukan_kayu_details' => ['hasil_hutan_bukan_kayu_id', 'bukan_kayu_id', 'unit', 'annual_volume_realization'],
            'pbphh' => ['cdk_id', 'status', 'number_of_workers', 'investment_value', 'regency_id', 'present_condition'],
            'pbphh_jenis_produksi' => ['pbphh_id', 'jenis_produksi_id'],
            'realisasi_pnbp' => ['year', 'month', 'cdk_id', 'status', 'pnbp_realization', 'pnbp_target', 'regency_id', 'id_pengelola_wisata'],
            'skps' => ['cdk_id', 'status', 'ps_area', 'number_of_kk', 'id_skema_perhutanan_sosial'],
            'nilai_ekonomi' => ['year', 'cdk_id', 'status', 'total_transaction_value', 'regency_id', 'nama_kelompok'],
            'perkembangan_kth' => ['cdk_id', 'status', 'luas_kelola', 'jumlah_anggota', 'kelas_kelembagaan'],
            'nilai_transaksi_ekonomi' => ['year', 'cdk_id', 'status', 'total_nilai_transaksi', 'regency_id', 'nama_kth'],
            'nilai_transaksi_ekonomi_details' => ['nilai_transaksi_ekonomi_id', 'commodity_id', 'nilai_transaksi'],
            'rekap_statistik_bulanan' => [
                'periode_tahun', 'periode_bulan', 'cdk_id', 'status', 'total_pegawai_aktif', 'total_laki',
                'total_perempuan', 'total_pns', 'total_pppk', 'total_honorer', 'total_pensiun_tahun_ini',
                'total_pensiun_bulan_ini', 'total_pensiun_6_bulan', 'kgb_jatuh_bulan_ini', 'kgb_jatuh_3_bulan',
                'statistik_status_pegawai', 'statistik_generasi', 'statistik_pendidikan', 'statistik_golongan',
                'statistik_status_pernikahan', 'statistik_bezetting', 'statistik_masa_kerja',
            ],
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->string($column)->nullable();
                $table->softDeletes();
            });
        }

        DB::table('cdks')->insert(['id' => 1, 'nama' => 'CDK A', 'is_active' => 1]);
        DB::table('m_bukan_kayu')->insert(['id' => 1, 'name' => 'Madu']);
        foreach (range((int) date('Y'), 2021) as $year) {
            DB::table('rekap_statistik_bulanan')->insert([
                'periode_tahun' => $year, 'periode_bulan' => 1, 'cdk_id' => 1,
                'status' => 'final', 'total_pegawai_aktif' => 0,
            ]);
        }
        $id = DB::table('hasil_hutan_bukan_kayu')->insertGetId([
            'year' => (int) date('Y'), 'month' => 1, 'cdk_id' => 1,
            'forest_type' => 'Hutan Negara', 'status' => 'final', 'volume_target' => 10,
        ]);
        DB::table('hasil_hutan_bukan_kayu_details')->insert([
            'hasil_hutan_bukan_kayu_id' => $id, 'bukan_kayu_id' => 1,
            'unit' => 'kg', 'annual_volume_realization' => 4,
        ]);
    }

    public function test_public_routes_keep_contract_and_reduce_cold_queries(): void
    {
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
        $version = (new \App\Http\Middleware\HandleInertiaRequests)->version(
            \Illuminate\Http\Request::create('/public/dashboard')
        );
        $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version]);
        $queries = 0;
        DB::listen(function () use (&$queries) { $queries++; });
        $year = (int) date('Y');
        $main = $this->get(route('public.dashboard', ['year' => $year, 'cdk_id' => 1]));
        $main->assertOk();
        $this->assertLessThanOrEqual(60, $queries);
        $this->assertEquals(4, $main->json('props.stats.bina_usaha.hutan_negara.bukan_kayu_by_unit.0.total'));

        Cache::flush();
        $queries = 0;
        $yoy = $this->get(route('public.dashboard-yoy', ['cdk_id' => 1]));
        $yoy->assertOk();
        $this->assertLessThanOrEqual(20 + 45 * count(range($year, 2021)), $queries);
        $this->assertEquals(4, $yoy->json("props.stats.$year.bina_usaha.hutan_negara.bukan_kayu_by_unit.0.total"));
    }
}
