<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Pemberdayaan\App\Exports\KupsExport;
use Modules\Pemberdayaan\App\Exports\NilaiEkonomiExport;
use Modules\Pemberdayaan\App\Exports\PerkembanganKthExport;
use Modules\Pemberdayaan\App\Exports\SkpsExport;
use Tests\TestCase;

class PemberdayaanExportModalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'pemberdayaan_export_testing', 'database.connections.pemberdayaan_export_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('pemberdayaan_export_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->unsignedBigInteger('cdk_id')->nullable();
        });
        Schema::create('m_regencies', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        Schema::create('m_districts', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        Schema::create('m_villages', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        Schema::create('m_skema_perhutanan_sosial', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        Schema::create('m_commodities', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->boolean('is_nilai_transaksi_ekonomi')->default(false); $table->softDeletes();
        });
        Schema::create('skps', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id'); $table->unsignedBigInteger('id_skema_perhutanan_sosial');
            $table->string('nama_kelompok'); $table->string('potential'); $table->string('ps_area');
            $table->string('number_of_kk'); $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('kups', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id'); $table->string('nama_kups'); $table->string('category');
            $table->string('commodity'); $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('nilai_ekonomi', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id'); $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('regency_id'); $table->unsignedBigInteger('district_id');
            $table->string('nama_kelompok'); $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('nilai_ekonomi_details', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('nilai_ekonomi_id'); $table->unsignedBigInteger('commodity_id');
            $table->decimal('production_volume'); $table->string('satuan'); $table->decimal('transaction_value');
        });
        Schema::create('perkembangan_kth', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id'); $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('regency_id'); $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('village_id'); $table->string('nama_kth'); $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable(); $table->timestamps(); $table->softDeletes();
        });

        DB::table('users')->insert(['id' => 7, 'name' => 'Operator CDK', 'cdk_id' => 42]);
        DB::table('m_regencies')->insert([['id' => 1, 'name' => 'Kabupaten A'], ['id' => 2, 'name' => 'Kabupaten B']]);
        DB::table('m_districts')->insert(['id' => 10, 'name' => 'Kecamatan A']);
        DB::table('m_villages')->insert(['id' => 100, 'name' => 'Desa A']);
        DB::table('m_skema_perhutanan_sosial')->insert(['id' => 1, 'name' => 'HKm']);
        DB::table('m_commodities')->insert(['id' => 1, 'name' => 'Kopi']);
    }

    public function test_skps_export_uses_selected_filters_and_columns(): void
    {
        DB::table('skps')->insert([
            ['id' => 1, 'cdk_id' => 42, 'regency_id' => 1, 'district_id' => 10, 'id_skema_perhutanan_sosial' => 1,
                'nama_kelompok' => 'Kelompok A', 'potential' => '10', 'ps_area' => '5', 'number_of_kk' => '3',
                'status' => 'draft', 'created_by' => 7, 'created_at' => '2025-04-01', 'updated_at' => '2025-04-01'],
            ['id' => 2, 'cdk_id' => 43, 'regency_id' => 2, 'district_id' => 10, 'id_skema_perhutanan_sosial' => 1,
                'nama_kelompok' => 'Kelompok B', 'potential' => '10', 'ps_area' => '5', 'number_of_kk' => '3',
                'status' => 'draft', 'created_by' => 7, 'created_at' => '2025-04-01', 'updated_at' => '2025-04-01'],
        ]);

        $export = new SkpsExport(['cdk_id' => 42, 'status' => 'draft', 'regency_id' => 1, 'columns' => ['group_name', 'creator']]);

        $this->assertSame([1], $export->query()->pluck('id')->all());
        $this->assertSame(['Nama Kelompok', 'Diinput Oleh'], $export->headings());
        $this->assertSame(['Kelompok A', 'Operator CDK'], $export->map($export->query()->first()));
    }

    public function test_kups_export_uses_selected_filters_and_columns(): void
    {
        DB::table('kups')->insert([
            ['id' => 1, 'cdk_id' => 42, 'regency_id' => 1, 'district_id' => 10, 'nama_kups' => 'KUPS A',
                'category' => 'Emas', 'commodity' => 'Kopi', 'status' => 'draft', 'created_by' => 7,
                'created_at' => '2025-04-01', 'updated_at' => '2025-04-01'],
            ['id' => 2, 'cdk_id' => 43, 'regency_id' => 2, 'district_id' => 10, 'nama_kups' => 'KUPS B',
                'category' => 'Perak', 'commodity' => 'Madu', 'status' => 'final', 'created_by' => 7,
                'created_at' => '2025-04-01', 'updated_at' => '2025-04-01'],
        ]);

        $export = new KupsExport(['cdk_id' => 42, 'status' => 'draft', 'regency_id' => 1, 'columns' => ['name', 'commodity']]);

        $this->assertSame([1], $export->query()->pluck('id')->all());
        $this->assertSame(['Nama KUPS', 'Komoditas'], $export->headings());
        $this->assertSame(['KUPS A', 'Kopi'], $export->map($export->query()->first()));
    }

    public function test_nilai_ekonomi_export_uses_period_location_and_selected_columns(): void
    {
        foreach ([[1, 42, 2025, 4, 1], [2, 43, 2025, 4, 2], [3, 42, 2025, 5, 1]] as [$id, $cdk, $year, $month, $regency]) {
            DB::table('nilai_ekonomi')->insert(['id' => $id, 'cdk_id' => $cdk, 'year' => $year, 'month' => $month,
                'regency_id' => $regency, 'district_id' => 10, 'nama_kelompok' => "Kelompok $id", 'status' => 'final',
                'created_by' => 7, 'created_at' => '2025-04-01', 'updated_at' => '2025-04-01']);
            DB::table('nilai_ekonomi_details')->insert(['nilai_ekonomi_id' => $id, 'commodity_id' => 1,
                'production_volume' => 2, 'satuan' => 'kg', 'transaction_value' => 100]);
        }

        $export = new NilaiEkonomiExport(2025, ['cdk_id' => 42, 'month' => 4, 'regency_id' => 1,
            'columns' => ['group_name', 'creator']]);

        $rows = $export->collection();
        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows->first()->parent->id);
        $this->assertSame(['Nama Kelompok', 'Diinput Oleh'], $export->headings());
        $this->assertSame(['Kelompok 1', 'Operator CDK'], $export->map($rows->first()));
    }

    public function test_perkembangan_kth_export_uses_period_location_and_selected_columns(): void
    {
        DB::table('perkembangan_kth')->insert([
            ['id' => 1, 'cdk_id' => 42, 'year' => 2025, 'month' => 4, 'regency_id' => 1, 'district_id' => 10,
                'village_id' => 100, 'nama_kth' => 'KTH A', 'status' => 'draft', 'created_by' => 7,
                'created_at' => '2025-04-01', 'updated_at' => '2025-04-01'],
            ['id' => 2, 'cdk_id' => 43, 'year' => 2025, 'month' => 4, 'regency_id' => 2, 'district_id' => 10,
                'village_id' => 100, 'nama_kth' => 'KTH B', 'status' => 'final', 'created_by' => 7,
                'created_at' => '2025-04-01', 'updated_at' => '2025-04-01'],
        ]);

        $export = new PerkembanganKthExport(2025, ['cdk_id' => 42, 'month' => 4, 'status' => 'draft',
            'regency_id' => 1, 'columns' => ['nama_kth', 'creator']]);

        $rows = $export->collection();
        $this->assertSame([1], $rows->pluck('id')->all());
        $this->assertSame(['Nama KTH', 'Diinput Oleh'], $export->headings());
        $this->assertSame(['KTH A', 'Operator CDK'], $export->map($rows->first()));
    }
}
