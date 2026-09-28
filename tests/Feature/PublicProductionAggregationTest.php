<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\User;
use App\Services\NonWoodProductionStats;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicProductionAggregationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'dashboard_testing', 'database.connections.dashboard_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'cache.default' => 'array']);
        DB::purge('dashboard_testing');
        Cache::flush();

        Schema::create('hasil_hutan_kayu', function (Blueprint $table) {
            $table->id(); $table->integer('year'); $table->integer('month'); $table->integer('cdk_id');
            $table->string('forest_type'); $table->string('status'); $table->decimal('volume_target'); $table->softDeletes();
        });
        Schema::create('hasil_hutan_kayu_details', function (Blueprint $table) {
            $table->id(); $table->integer('hasil_hutan_kayu_id'); $table->integer('kayu_id'); $table->decimal('volume_realization');
        });
        Schema::create('m_kayu', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('hasil_hutan_bukan_kayu', function (Blueprint $table) {
            $table->id(); $table->integer('year'); $table->integer('month'); $table->integer('cdk_id');
            $table->string('forest_type'); $table->string('status'); $table->decimal('volume_target'); $table->softDeletes();
        });
        Schema::create('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
            $table->id(); $table->integer('hasil_hutan_bukan_kayu_id'); $table->integer('bukan_kayu_id');
            $table->string('unit'); $table->decimal('annual_volume_realization');
        });
        Schema::create('m_bukan_kayu', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('pbphh', function (Blueprint $table) {
            $table->id(); $table->integer('cdk_id'); $table->string('status'); $table->integer('number_of_workers');
            $table->decimal('investment_value'); $table->integer('regency_id'); $table->string('present_condition'); $table->softDeletes();
        });
        Schema::create('m_regencies', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('pbphh_jenis_produksi', function (Blueprint $table) {
            $table->integer('pbphh_id'); $table->integer('jenis_produksi_id');
        });
        Schema::create('m_jenis_produksi', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('realisasi_pnbp', function (Blueprint $table) {
            $table->id(); $table->integer('cdk_id'); $table->integer('year'); $table->integer('month');
            $table->string('status'); $table->string('pnbp_realization'); $table->decimal('pnbp_target');
            $table->integer('regency_id'); $table->integer('id_pengelola_wisata'); $table->softDeletes();
        });
        Schema::create('m_pengelola_wisata', function (Blueprint $table) { $table->id(); $table->string('name'); });

        DB::table('m_kayu')->insert(['id' => 1, 'name' => 'Jati']);
        DB::table('m_bukan_kayu')->insert([['id' => 1, 'name' => 'Madu'], ['id' => 2, 'name' => 'Bambu']]);
        DB::table('m_regencies')->insert(['id' => 1, 'name' => 'Kabupaten A']);
        DB::table('m_jenis_produksi')->insert(['id' => 1, 'name' => 'Olahan']);
        DB::table('m_pengelola_wisata')->insert(['id' => 1, 'name' => 'Pengelola A']);
    }

    private function wood(int $cdk, int $month, float $target, float $realization, array $extra = []): void
    {
        $id = DB::table('hasil_hutan_kayu')->insertGetId(array_merge([
            'year' => 2026, 'month' => $month, 'cdk_id' => $cdk, 'forest_type' => 'Hutan Negara',
            'status' => 'final', 'volume_target' => $target,
        ], $extra));
        DB::table('hasil_hutan_kayu_details')->insert([
            'hasil_hutan_kayu_id' => $id, 'kayu_id' => 1, 'volume_realization' => $realization,
        ]);
    }

    private function nonWood(int $cdk, string $unit, float $target, float $realization, int $commodity, array $extra = []): void
    {
        $id = DB::table('hasil_hutan_bukan_kayu')->insertGetId(array_merge([
            'year' => 2026, 'month' => 1, 'cdk_id' => $cdk, 'forest_type' => 'Hutan Negara',
            'status' => 'final', 'volume_target' => $target,
        ], $extra));
        DB::table('hasil_hutan_bukan_kayu_details')->insert([
            'hasil_hutan_bukan_kayu_id' => $id, 'bukan_kayu_id' => $commodity,
            'unit' => $unit, 'annual_volume_realization' => $realization,
        ]);
    }

    private function production(int $year, ?int $cdk, ?array $prefetchedNonWood = null): array
    {
        $method = new \ReflectionMethod(DashboardController::class, 'getBinaUsahaStats');
        return $method->invoke(new DashboardController, $year, $cdk, $prefetchedNonWood);
    }

    public function test_production_contract_values_and_filters_are_preserved(): void
    {
        $this->wood(1, 1, 100, 40);
        $this->wood(1, 2, 50, 20);
        $this->wood(1, 1, 30, 15, ['forest_type' => 'Hutan Rakyat']);
        $this->wood(1, 1, 12, 8, ['forest_type' => 'Perhutanan Sosial']);
        $this->wood(2, 1, 200, 100);
        $this->wood(1, 1, 500, 500, ['status' => 'draft']);
        $this->wood(1, 1, 300, 300, ['deleted_at' => '2026-01-01']);
        $this->nonWood(1, 'kg', 20, 10, 1);
        $this->nonWood(1, 'liter', 10, 5, 1);
        $this->nonWood(1, 'batang', 7, 3, 2);
        $this->nonWood(2, 'kg', 50, 20, 1);
        $this->nonWood(1, 'kg', 100, 100, 1, ['status' => 'draft']);
        $this->nonWood(1, 'kg', 100, 100, 1, ['deleted_at' => '2026-01-01']);
        DB::table('realisasi_pnbp')->insert([
            ['year' => 2026, 'month' => 1, 'cdk_id' => 1, 'status' => 'final', 'pnbp_realization' => '25.50', 'pnbp_target' => 50, 'regency_id' => 1, 'id_pengelola_wisata' => 1],
            ['year' => 2026, 'month' => 2, 'cdk_id' => 1, 'status' => 'final', 'pnbp_realization' => '14.50', 'pnbp_target' => 20, 'regency_id' => 1, 'id_pengelola_wisata' => 1],
            ['year' => 2026, 'month' => 1, 'cdk_id' => 2, 'status' => 'final', 'pnbp_realization' => '100', 'pnbp_target' => 200, 'regency_id' => 1, 'id_pengelola_wisata' => 1],
            ['year' => 2026, 'month' => 1, 'cdk_id' => 1, 'status' => 'draft', 'pnbp_realization' => '100', 'pnbp_target' => 200, 'regency_id' => 1, 'id_pengelola_wisata' => 1],
        ]);

        $queries = 0;
        DB::listen(function () use (&$queries) { $queries++; });
        $filtered = $this->production(2026, 1);
        $this->assertLessThanOrEqual(19, $queries);
        $forest = $filtered['hutan_negara'];
        $this->assertEquals(60, $forest['kayu_total']);
        $this->assertEquals(150, $forest['kayu_target']);
        $this->assertEquals(40, $forest['kayu_monthly'][1]);
        $this->assertEquals(20, $forest['kayu_monthly'][2]);
        $this->assertEquals(60, $forest['kayu_commodity']['Jati']);
        $this->assertEquals(15, $forest['bukan_kayu_total']);
        $this->assertEquals(30, $forest['bukan_kayu_target']);
        $this->assertEquals(3, $forest['bambu_total']);
        $this->assertEquals(7, $forest['bambu_target']);
        $this->assertEquals(18, $forest['bukan_kayu_monthly'][1]);
        $this->assertEquals(15, $forest['bukan_kayu_commodity']['Madu']);
        $this->assertSame(['kg', 'liter', 'batang'], array_column($forest['bukan_kayu_by_unit'], 'unit'));
        $this->assertSame([], $filtered['hutan_rakyat']['bukan_kayu_by_unit']);
        $this->assertEquals(15, $filtered['hutan_rakyat']['kayu_total']);
        $this->assertEquals(30, $filtered['hutan_rakyat']['kayu_target']);
        $this->assertEquals(8, $filtered['perhutanan_sosial']['kayu_total']);
        $this->assertEquals(40, $filtered['pnbp']['total_realization']);
        $this->assertEquals(70, $filtered['pnbp']['total_target']);
        $this->assertEquals(25.5, $filtered['pnbp']['monthly'][1]['realization']);
        $this->assertEquals(14.5, $filtered['pnbp']['monthly'][2]['realization']);

        Cache::flush();
        $all = $this->production(2026, null)['hutan_negara'];
        $this->assertEquals(160, $all['kayu_total']);
        $this->assertEquals(35, $all['bukan_kayu_total']);
        $this->assertEquals(80, $all['bukan_kayu_target']);

        $empty = $this->production(2024, 1)['hutan_negara'];
        $this->assertEquals(0, $empty['kayu_total']);
        $this->assertSame([], $empty['bukan_kayu_by_unit']);
        $this->assertCount(12, $empty['kayu_monthly']);
    }

    public function test_yoy_prefetch_uses_one_unit_aggregation_and_reuses_static_pbphh(): void
    {
        $this->nonWood(1, 'kg', 10, 4, 1);
        $this->nonWood(1, 'liter', 20, 6, 1, ['year' => 2025]);
        DB::table('pbphh')->insert([
            'cdk_id' => 1, 'status' => 'final', 'number_of_workers' => 12,
            'investment_value' => 100, 'regency_id' => 1, 'present_condition' => '1',
        ]);

        $unitQueries = 0;
        $pbphhQueries = 0;
        DB::listen(function ($query) use (&$unitQueries, &$pbphhQueries) {
            if (str_contains($query->sql, 'from "hasil_hutan_bukan_kayu_details" as "d"')) $unitQueries++;
            if (str_contains($query->sql, 'from "pbphh"')) $pbphhQueries++;
        });
        $prefetched = app(NonWoodProductionStats::class)->forYears([2026, 2025], 1);
        $current = $this->production(2026, 1, $prefetched[2026] ?? []);
        $previous = $this->production(2025, 1, $prefetched[2025] ?? []);

        $this->assertSame(1, $unitQueries);
        $this->assertSame(6, $pbphhQueries);
        $this->assertEquals(1, $current['pbphh']['total_units']);
        $this->assertEquals(12, $previous['pbphh']['total_workers']);
        $this->assertEquals(4, $current['hutan_negara']['bukan_kayu_by_unit'][0]['total']);
        $this->assertEquals(6, $previous['hutan_negara']['bukan_kayu_by_unit'][0]['total']);
    }

    public function test_legacy_hhbk_totals_keep_their_original_scope_for_authenticated_cdk_users(): void
    {
        $this->nonWood(1, 'kg', 10, 5, 1);
        $this->nonWood(2, 'kg', 20, 7, 1);
        $this->nonWood(2, 'batang', 30, 3, 2);
        $this->actingAs(new User(['cdk_id' => 1]));

        $forest = $this->production(2026, null)['hutan_negara'];

        $this->assertEquals(12, $forest['bukan_kayu_total']);
        $this->assertEquals(3, $forest['bambu_total']);
        $this->assertEquals(10, $forest['bukan_kayu_target']);
        $this->assertEquals(5, $forest['bukan_kayu_commodity']['Madu']);
        $this->assertEquals(12, $forest['bukan_kayu_by_unit'][0]['total']);
    }
}
