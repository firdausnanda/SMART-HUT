<?php

namespace Tests\Feature;

use App\Services\NonWoodProductionStats;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NonWoodProductionStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Dedicated in-memory connection: never migrate or clear the application database.
        config(['database.default' => 'hhbk_testing', 'database.connections.hhbk_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('hhbk_testing');
        Schema::create('hasil_hutan_bukan_kayu', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->integer('cdk_id');
            $table->string('forest_type');
            $table->string('status');
            $table->softDeletes();
        });
        Schema::create('m_bukan_kayu', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
            $table->id();
            $table->integer('hasil_hutan_bukan_kayu_id');
            $table->integer('bukan_kayu_id');
            $table->string('unit')->nullable();
            $table->decimal('annual_volume_realization', 14, 2);
        });
        DB::table('m_bukan_kayu')->insert([
            ['id' => 1, 'name' => 'Bambu'], ['id' => 2, 'name' => 'Madu'],
            ['id' => 3, 'name' => 'Aren'], ['id' => 4, 'name' => 'Kopi'],
            ['id' => 5, 'name' => 'Porang'], ['id' => 6, 'name' => 'Rotan'],
            ['id' => 7, 'name' => 'Getah'],
        ]);
    }

    private function record(string $forest, ?string $unit, float $value, int $commodity = 2, array $overrides = []): void
    {
        $id = DB::table('hasil_hutan_bukan_kayu')->insertGetId(array_merge([
            'year' => 2026, 'month' => 1, 'cdk_id' => 1, 'forest_type' => $forest,
            'status' => 'final', 'deleted_at' => null,
        ], $overrides));
        DB::table('hasil_hutan_bukan_kayu_details')->insert([
            'hasil_hutan_bukan_kayu_id' => $id, 'bukan_kayu_id' => $commodity,
            'unit' => $unit, 'annual_volume_realization' => $value,
        ]);
    }

    public function test_units_commodities_and_months_remain_separate_for_every_forest(): void
    {
        foreach (['Hutan Negara', 'Perhutanan Sosial', 'Hutan Rakyat'] as $forest) {
            $this->record($forest, ' Kg ', 100.25);
            $this->record($forest, 'kg', 20.5, 2, ['month' => 2]);
            $this->record($forest, 'liter', 30, 2);
            $this->record($forest, 'batang', 50, 1);
            $this->record($forest, 'ton', 2, 2);
            foreach (range(3, 7) as $commodity) $this->record($forest, 'kg', 5, $commodity);
        }
        $result = app(NonWoodProductionStats::class)->forYear(2026, 1);
        $this->assertCount(3, $result);
        foreach ($result as $forest) {
            $groups = array_column($forest['bukan_kayu_by_unit'], null, 'unit');
            $this->assertSame(['kg', 'ton', 'liter', 'batang'], array_keys($groups));
            $this->assertEquals(145.75, $groups['kg']['total']);
            $this->assertEquals(20.5, $groups['kg']['monthly'][2]);
            $this->assertEquals(0, $groups['kg']['monthly'][12]);
            $this->assertSame('Bambu', $groups['batang']['commodities'][0]['name']);
            $this->assertEquals(50, $groups['batang']['total']);
            $this->assertEquals(30, $groups['liter']['total']);
            $this->assertEquals(2, $groups['ton']['total']);
            $this->assertSame(['Madu', 'Aren', 'Getah', 'Kopi', 'Porang', 'Rotan'], array_column($groups['kg']['commodities'], 'name'));
            foreach ($groups as $group) {
                $this->assertCount(12, $group['monthly']);
                $this->assertEquals($group['total'], array_sum($group['monthly']));
                $this->assertEquals($group['total'], array_sum(array_column($group['commodities'], 'total')));
            }
        }
    }

    public function test_unspecified_units_keep_original_units_and_commodity_details(): void
    {
        foreach ([null, '', 'lainnya', 'karung', ' Karung '] as $unit) {
            $this->record('Hutan Negara', $unit, 10);
            $this->record('Hutan Negara', $unit, 20, 1);
        }
        $forest = app(NonWoodProductionStats::class)->forYear(2026)['hutan_negara'];
        $this->assertSame([], $forest['bukan_kayu_by_unit']);
        $this->assertCount(10, $forest['bukan_kayu_unspecified']);
        $this->assertContains(' Karung ', array_column($forest['bukan_kayu_unspecified'], 'unit'));
        $this->assertArrayNotHasKey('total', $forest);
    }

    public function test_filters_and_missing_year_differ_from_explicit_zero(): void
    {
        $this->record('Hutan Negara', 'kg', 10);
        $this->record('Hutan Negara', 'kg', 20, 2, ['cdk_id' => 2]);
        $this->record('Hutan Negara', 'kg', 100, 2, ['status' => 'draft']);
        $this->record('Hutan Negara', 'kg', 200, 2, ['deleted_at' => '2026-01-01 00:00:00']);
        $this->record('Hutan Negara', 'kg', 0, 2, ['year' => 2025]);
        $service = app(NonWoodProductionStats::class);
        $this->assertEquals(10, $service->forYear(2026, 1)['hutan_negara']['bukan_kayu_by_unit'][0]['total']);
        $this->assertEquals(30, $service->forYear(2026)['hutan_negara']['bukan_kayu_by_unit'][0]['total']);
        $this->assertEquals(0, $service->forYear(2025)['hutan_negara']['bukan_kayu_by_unit'][0]['total']);
        $this->assertSame([], $service->forYear(2024));
        $this->assertSame([], $service->forYear(2026, 999));
    }
}
