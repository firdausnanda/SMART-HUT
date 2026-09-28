<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicRehabProtectionAggregationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'dashboard_testing', 'database.connections.dashboard_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'cache.default' => 'array']);
        DB::purge('dashboard_testing');
        Cache::flush();

        foreach (['rehab_lahan', 'penghijauan_lingkungan', 'rehab_manggrove', 'reboisasi_ps'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->integer('year'); $table->integer('month'); $table->integer('cdk_id');
                $table->string('status'); $table->decimal('realization'); $table->decimal('target_annual');
                $table->string('fund_source'); $table->integer('regency_id'); $table->integer('pengelola_id')->nullable();
                $table->softDeletes();
            });
        }
        Schema::create('rhl_teknis', function (Blueprint $table) {
            $table->id(); $table->integer('year'); $table->integer('month'); $table->integer('cdk_id');
            $table->string('status'); $table->decimal('target_annual'); $table->string('fund_source'); $table->softDeletes();
        });
        Schema::create('rhl_teknis_details', function (Blueprint $table) {
            $table->id(); $table->integer('rhl_teknis_id'); $table->integer('bangunan_kta_id'); $table->integer('unit_amount');
        });
        Schema::create('m_sumber_dana', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('m_regencies', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('m_pengelola_ps', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('m_bangunan_kta', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('m_pengelola_wisata', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('kebakaran_hutan', function (Blueprint $table) {
            $table->id(); $table->integer('year'); $table->integer('month'); $table->integer('cdk_id');
            $table->string('status'); $table->integer('number_of_fires'); $table->string('fire_area');
            $table->integer('id_pengelola_wisata'); $table->softDeletes();
        });
        Schema::create('pengunjung_wisata', function (Blueprint $table) {
            $table->id(); $table->integer('year'); $table->integer('month'); $table->integer('cdk_id');
            $table->string('status'); $table->integer('number_of_visitors'); $table->decimal('gross_income');
            $table->integer('id_pengelola_wisata'); $table->softDeletes();
        });

        DB::table('m_sumber_dana')->insert(['id' => 1, 'name' => 'APBD']);
        DB::table('m_regencies')->insert(['id' => 1, 'name' => 'Kabupaten A']);
        DB::table('m_pengelola_ps')->insert(['id' => 1, 'name' => 'Kelompok A']);
        DB::table('m_bangunan_kta')->insert(['id' => 1, 'name' => 'Dam']);
        DB::table('m_pengelola_wisata')->insert(['id' => 1, 'name' => 'Pengelola A']);
    }

    private function stats(string $method, int $year = 2026, ?int $cdk = 1): array
    {
        return (new \ReflectionMethod(DashboardController::class, $method))
            ->invoke(new DashboardController, $year, $cdk);
    }

    public function test_rehabilitation_totals_match_monthly_data_and_exclude_other_records(): void
    {
        foreach ([
            ['month' => 1, 'realization' => 10, 'target_annual' => 20],
            ['month' => 2, 'realization' => 15, 'target_annual' => 30],
            ['month' => 1, 'realization' => 100, 'target_annual' => 100, 'status' => 'draft'],
            ['month' => 1, 'realization' => 100, 'target_annual' => 100, 'cdk_id' => 2],
            ['month' => 1, 'realization' => 100, 'target_annual' => 100, 'deleted_at' => '2026-01-01'],
        ] as $values) {
            DB::table('rehab_lahan')->insert(array_merge([
                'year' => 2026, 'month' => 1, 'cdk_id' => 1, 'status' => 'final',
                'realization' => 0, 'target_annual' => 0, 'fund_source' => 'APBD', 'regency_id' => 1,
            ], $values));
        }
        $rhlId = DB::table('rhl_teknis')->insertGetId([
            'year' => 2026, 'month' => 1, 'cdk_id' => 1, 'status' => 'final',
            'target_annual' => 7, 'fund_source' => 'APBD',
        ]);
        DB::table('rhl_teknis_details')->insert([
            ['rhl_teknis_id' => $rhlId, 'bangunan_kta_id' => 1, 'unit_amount' => 2],
            ['rhl_teknis_id' => $rhlId, 'bangunan_kta_id' => 1, 'unit_amount' => 3],
        ]);

        $queries = 0;
        DB::listen(function () use (&$queries) { $queries++; });
        $stats = $this->stats('getPembinaanStats');
        $this->assertLessThanOrEqual(17, $queries);
        $this->assertEquals(25, $stats['rehab_total']);
        $this->assertEquals(50, $stats['rehab_target_total']);
        $this->assertEquals(10, $stats['rehab_chart'][1]);
        $this->assertEquals(15, $stats['rehab_chart'][2]);
        $this->assertEquals(5, $stats['rhl_teknis_total']);
        $this->assertEquals(7, $stats['rhl_teknis_target_total']);
        $this->assertEquals(5, $stats['rhl_teknis_chart'][1]);
        $this->assertEquals(7, $stats['rhl_teknis_target_chart'][1]);
        $this->assertEquals(0, $stats['manggrove_total']);
        $this->assertEquals(0, $this->stats('getPembinaanStats', 2024)['rehab_total']);
    }

    public function test_protection_totals_match_monthly_data(): void
    {
        DB::table('kebakaran_hutan')->insert([
            ['year' => 2026, 'month' => 1, 'cdk_id' => 1, 'status' => 'final', 'number_of_fires' => 2, 'fire_area' => '1.5', 'id_pengelola_wisata' => 1],
            ['year' => 2026, 'month' => 2, 'cdk_id' => 1, 'status' => 'final', 'number_of_fires' => 3, 'fire_area' => '2.5', 'id_pengelola_wisata' => 1],
            ['year' => 2026, 'month' => 1, 'cdk_id' => 2, 'status' => 'final', 'number_of_fires' => 9, 'fire_area' => '9', 'id_pengelola_wisata' => 1],
            ['year' => 2026, 'month' => 1, 'cdk_id' => 1, 'status' => 'draft', 'number_of_fires' => 9, 'fire_area' => '9', 'id_pengelola_wisata' => 1],
        ]);
        DB::table('pengunjung_wisata')->insert([
            ['year' => 2026, 'month' => 1, 'cdk_id' => 1, 'status' => 'final', 'number_of_visitors' => 20, 'gross_income' => 100, 'id_pengelola_wisata' => 1],
            ['year' => 2026, 'month' => 2, 'cdk_id' => 1, 'status' => 'final', 'number_of_visitors' => 30, 'gross_income' => 150, 'id_pengelola_wisata' => 1],
        ]);

        $queries = 0;
        DB::listen(function () use (&$queries) { $queries++; });
        $stats = $this->stats('getPerlindunganStats');
        $this->assertLessThanOrEqual(4, $queries);
        $this->assertEquals(5, $stats['kebakaran_kejadian']);
        $this->assertEquals(4, $stats['kebakaran_area']);
        $this->assertEquals(2, $stats['kebakaranMonthly'][1]['incidents']);
        $this->assertEquals(50, $stats['wisata_visitors']);
        $this->assertEquals(250, $stats['wisata_income']);
        $this->assertEquals(30, $stats['wisataMonthly'][2]['visitors']);
        $this->assertEquals(0, $this->stats('getPerlindunganStats', 2024)['kebakaran_kejadian']);
    }
}
