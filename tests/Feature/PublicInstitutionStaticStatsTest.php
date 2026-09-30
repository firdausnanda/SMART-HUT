<?php

namespace Tests\Feature;

use Modules\Dashboard\App\Http\Controllers\DashboardController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicInstitutionStaticStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'dashboard_testing', 'database.connections.dashboard_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'cache.default' => 'array']);
        DB::purge('dashboard_testing');
        Cache::flush();

        Schema::create('skps', function (Blueprint $table) {
            $table->id(); $table->integer('cdk_id'); $table->string('status');
            $table->decimal('ps_area'); $table->integer('number_of_kk');
            $table->integer('id_skema_perhutanan_sosial'); $table->softDeletes();
        });
        Schema::create('m_skema_perhutanan_sosial', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('nilai_ekonomi', function (Blueprint $table) {
            $table->id(); $table->integer('cdk_id'); $table->integer('year'); $table->string('status');
            $table->decimal('total_transaction_value'); $table->integer('regency_id');
            $table->string('nama_kelompok'); $table->softDeletes();
        });
        Schema::create('perkembangan_kth', function (Blueprint $table) {
            $table->id(); $table->integer('cdk_id'); $table->string('status');
            $table->decimal('luas_kelola'); $table->integer('jumlah_anggota');
            $table->string('kelas_kelembagaan'); $table->softDeletes();
        });
        Schema::create('nilai_transaksi_ekonomi', function (Blueprint $table) {
            $table->id(); $table->integer('cdk_id'); $table->integer('year'); $table->string('status');
            $table->decimal('total_nilai_transaksi'); $table->integer('regency_id');
            $table->string('nama_kth'); $table->softDeletes();
        });
        Schema::create('nilai_transaksi_ekonomi_details', function (Blueprint $table) {
            $table->id(); $table->integer('nilai_transaksi_ekonomi_id');
            $table->integer('commodity_id'); $table->decimal('nilai_transaksi');
        });
        Schema::create('m_commodities', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('m_regencies', function (Blueprint $table) { $table->id(); $table->string('name'); });

        DB::table('m_skema_perhutanan_sosial')->insert(['id' => 1, 'name' => 'HKm']);
        DB::table('m_commodities')->insert(['id' => 1, 'name' => 'Kopi']);
        DB::table('m_regencies')->insert(['id' => 1, 'name' => 'Kabupaten A']);
        DB::table('skps')->insert(['cdk_id' => 1, 'status' => 'final', 'ps_area' => 8, 'number_of_kk' => 10, 'id_skema_perhutanan_sosial' => 1]);
        DB::table('perkembangan_kth')->insert(['cdk_id' => 1, 'status' => 'final', 'luas_kelola' => 9, 'jumlah_anggota' => 11, 'kelas_kelembagaan' => 'Madya']);
    }

    public function test_year_independent_distributions_are_loaded_once_for_yoy(): void
    {
        $schemes = 0;
        $classes = 0;
        DB::listen(function ($query) use (&$schemes, &$classes) {
            if (str_contains($query->sql, 'from "m_skema_perhutanan_sosial"')) $schemes++;
            if (str_contains($query->sql, 'from "perkembangan_kth"') && str_contains($query->sql, 'group by')) $classes++;
        });
        $controller = new DashboardController;
        $ps = new \ReflectionMethod(DashboardController::class, 'getKelembagaanPsStats');
        $hr = new \ReflectionMethod(DashboardController::class, 'getKelembagaanHrStats');
        $currentPs = $ps->invoke($controller, 2026, 1);
        $previousPs = $ps->invoke($controller, 2025, 1);
        $currentHr = $hr->invoke($controller, 2026, 1);
        $previousHr = $hr->invoke($controller, 2025, 1);

        $this->assertSame(1, $schemes);
        $this->assertSame(1, $classes);
        $this->assertEquals(1, $currentPs['kelompok_count']);
        $this->assertEquals($currentPs['scheme_distribution'], $previousPs['scheme_distribution']);
        $this->assertEquals(1, $currentHr['kelompok_count']);
        $this->assertEquals($currentHr['class_distribution'], $previousHr['class_distribution']);
    }
}
