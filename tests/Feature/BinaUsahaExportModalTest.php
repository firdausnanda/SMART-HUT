<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\BinaUsaha\App\Exports\HasilHutanBukanKayuExport;
use Modules\BinaUsaha\App\Exports\HasilHutanKayuExport;
use Modules\BinaUsaha\App\Exports\PbphhExport;
use Modules\BinaUsaha\App\Exports\RealisasiPnbpExport;
use Tests\TestCase;

class BinaUsahaExportModalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'bina_usaha_export_testing', 'database.connections.bina_usaha_export_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('bina_usaha_export_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->unsignedBigInteger('cdk_id')->nullable();
        });
        foreach (['m_regencies', 'm_districts', 'm_pengelola_hutan', 'm_pengelola_wisata', 'm_kayu', 'm_bukan_kayu', 'm_jenis_produksi'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->string('name');
            });
        }
        foreach (['hasil_hutan_kayu', 'hasil_hutan_bukan_kayu'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('cdk_id'); $table->integer('year'); $table->integer('month');
                $table->string('forest_type'); $table->unsignedBigInteger('regency_id');
                $table->unsignedBigInteger('district_id'); $table->unsignedBigInteger('pengelola_hutan_id')->nullable();
                $table->unsignedBigInteger('pengelola_wisata_id')->nullable(); $table->decimal('volume_target');
                $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps(); $table->softDeletes();
            });
        }
        Schema::create('hasil_hutan_kayu_details', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('hasil_hutan_kayu_id');
            $table->unsignedBigInteger('kayu_id'); $table->decimal('volume_realization');
        });
        Schema::create('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('hasil_hutan_bukan_kayu_id');
            $table->unsignedBigInteger('bukan_kayu_id'); $table->decimal('annual_volume_realization');
            $table->string('unit');
        });
        Schema::create('pbphh', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id'); $table->string('name');
            $table->string('number'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id'); $table->decimal('investment_value');
            $table->integer('number_of_workers'); $table->boolean('present_condition');
            $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('pbphh_jenis_produksi', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('pbphh_id');
            $table->unsignedBigInteger('jenis_produksi_id'); $table->decimal('kapasitas_ijin');
            $table->timestamps();
        });
        Schema::create('realisasi_pnbp', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id'); $table->integer('year');
            $table->integer('month'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('id_pengelola_wisata'); $table->string('types_of_forest_products');
            $table->decimal('pnbp_target'); $table->decimal('pnbp_realization');
            $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });

        DB::table('users')->insert(['id' => 7, 'name' => 'Operator CDK', 'cdk_id' => 42]);
        DB::table('m_regencies')->insert(['id' => 1, 'name' => 'Kabupaten A']);
        DB::table('m_districts')->insert(['id' => 10, 'name' => 'Kecamatan A']);
        DB::table('m_pengelola_hutan')->insert(['id' => 5, 'name' => 'Pengelola Hutan A']);
        DB::table('m_pengelola_wisata')->insert(['id' => 6, 'name' => 'Pengelola Wisata A']);
    }

    public function test_forest_exports_apply_cdk_period_status_location_and_columns(): void
    {
        foreach (['hasil_hutan_kayu' => HasilHutanKayuExport::class,
            'hasil_hutan_bukan_kayu' => HasilHutanBukanKayuExport::class] as $table => $class) {
            foreach ([[1, 42, 2025, 4, 1, 'draft'], [2, 43, 2025, 4, 1, 'draft'],
                [3, 42, 2025, 5, 1, 'draft'], [4, 42, 2025, 4, 1, 'final'],
                [5, 42, 2025, 4, 2, 'draft'], [6, 42, 2024, 4, 1, 'draft']] as
                [$id, $cdk, $year, $month, $regency, $status]) {
                DB::table($table)->insert(['id' => $id, 'cdk_id' => $cdk, 'year' => $year,
                    'month' => $month, 'forest_type' => 'Hutan Negara', 'regency_id' => $regency,
                    'district_id' => 10, 'pengelola_hutan_id' => 5, 'volume_target' => 10,
                    'status' => $status, 'created_by' => 7,
                    'created_at' => '2025-04-01', 'updated_at' => '2025-04-01']);
            }

            $export = new $class('Hutan Negara', 2025, ['month' => 4, 'status' => 'draft',
                'cdk_id' => 42, 'regency_id' => 1, 'district_id' => 10,
                'columns' => ['year', 'creator']]);
            $this->assertSame([1], $export->query()->pluck('id')->all(), $table);
            $this->assertSame(['Tahun', 'Diinput Oleh'], $export->headings(), $table);
            $this->assertSame([2025, 'Operator CDK'], $export->map($export->query()->first()), $table);
        }
    }

    public function test_pbphh_export_applies_cdk_status_location_and_columns(): void
    {
        foreach ([[1, 42, 1, 'draft'], [2, 43, 1, 'draft'],
            [3, 42, 2, 'draft'], [4, 42, 1, 'final']] as [$id, $cdk, $regency, $status]) {
            DB::table('pbphh')->insert(['id' => $id, 'cdk_id' => $cdk, 'name' => "Industri $id",
                'number' => "IZIN-$id", 'regency_id' => $regency, 'district_id' => 10,
                'investment_value' => 1000, 'number_of_workers' => 3, 'present_condition' => true,
                'status' => $status, 'created_by' => 7,
                'created_at' => '2025-04-01', 'updated_at' => '2025-04-01']);
        }

        $export = new PbphhExport(['cdk_id' => 42, 'status' => 'draft',
            'regency_id' => 1, 'district_id' => 10, 'columns' => ['name', 'creator']]);
        $this->assertSame([1], $export->query()->pluck('id')->all());
        $this->assertSame(['Nama Industri', 'Diinput Oleh'], $export->headings());
        $this->assertSame(['Industri 1', 'Operator CDK'], $export->map($export->query()->first()));
    }

    public function test_pnbp_export_applies_cdk_period_status_manager_and_columns(): void
    {
        foreach ([[1, 42, 2025, 4, 6, 'draft'], [2, 43, 2025, 4, 6, 'draft'],
            [3, 42, 2025, 5, 6, 'draft'], [4, 42, 2025, 4, 6, 'final'],
            [5, 42, 2025, 4, 7, 'draft'], [6, 42, 2024, 4, 6, 'draft']] as
            [$id, $cdk, $year, $month, $manager, $status]) {
            DB::table('realisasi_pnbp')->insert(['id' => $id, 'cdk_id' => $cdk, 'year' => $year,
                'month' => $month, 'regency_id' => 1, 'id_pengelola_wisata' => $manager,
                'types_of_forest_products' => 'Kayu', 'pnbp_target' => 100, 'pnbp_realization' => 50,
                'status' => $status, 'created_by' => 7,
                'created_at' => '2025-04-01', 'updated_at' => '2025-04-01']);
        }

        $export = new RealisasiPnbpExport(2025, ['month' => 4, 'status' => 'draft',
            'cdk_id' => 42, 'regency_id' => 1, 'pengelola_wisata_id' => 6,
            'columns' => ['types_of_forest_products', 'creator']]);
        $this->assertSame([1], $export->query()->pluck('id')->all());
        $this->assertSame(['Jenis Hasil Hutan', 'Diinput Oleh'], $export->headings());
        $this->assertSame(['Kayu', 'Operator CDK'], $export->map($export->query()->first()));
    }
}
