<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Perlindungan\App\Exports\KebakaranHutanExport;
use Modules\Perlindungan\App\Exports\PengunjungWisataExport;
use Tests\TestCase;

class PerlindunganExportModalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'perlindungan_export_testing', 'database.connections.perlindungan_export_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('perlindungan_export_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name');
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
        Schema::create('m_pengelola_wisata', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        Schema::create('kebakaran_hutan', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id'); $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('regency_id'); $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('village_id'); $table->unsignedBigInteger('id_pengelola_wisata');
            $table->string('area_function'); $table->integer('number_of_fires'); $table->string('fire_area');
            $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('pengunjung_wisata', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id'); $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('id_pengelola_wisata'); $table->integer('number_of_visitors');
            $table->decimal('gross_income'); $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable(); $table->timestamps(); $table->softDeletes();
        });

        DB::table('users')->insert(['id' => 7, 'name' => 'Operator CDK']);
        DB::table('m_regencies')->insert(['id' => 1, 'name' => 'Kabupaten A']);
        DB::table('m_districts')->insert(['id' => 10, 'name' => 'Kecamatan A']);
        DB::table('m_villages')->insert(['id' => 100, 'name' => 'Desa A']);
        DB::table('m_pengelola_wisata')->insert([['id' => 5, 'name' => 'Pengelola A'], ['id' => 6, 'name' => 'Pengelola B']]);
    }

    public function test_kebakaran_export_uses_filters_and_selected_columns(): void
    {
        foreach ([[1, 42, 2025, 4, 1, 10, 5, 'draft'], [2, 43, 2025, 4, 1, 10, 5, 'draft'],
            [3, 42, 2025, 5, 1, 10, 5, 'draft'], [4, 42, 2025, 4, 1, 10, 5, 'final'],
            [5, 42, 2025, 4, 1, 10, 6, 'draft'], [6, 42, 2025, 4, 2, 10, 5, 'draft'],
            [7, 42, 2025, 4, 1, 11, 5, 'draft'], [8, 42, 2024, 4, 1, 10, 5, 'draft']] as
            [$id, $cdk, $year, $month, $regency, $district, $manager, $status]) {
            DB::table('kebakaran_hutan')->insert([
                'id' => $id, 'cdk_id' => $cdk, 'year' => $year, 'month' => $month,
                'regency_id' => $regency, 'district_id' => $district, 'village_id' => 100,
                'id_pengelola_wisata' => $manager, 'area_function' => 'Hutan',
                'number_of_fires' => 2, 'fire_area' => '1.5', 'status' => $status,
                'created_by' => 7, 'created_at' => '2025-04-01', 'updated_at' => '2025-04-01',
            ]);
        }

        $export = new KebakaranHutanExport(2025, [
            'month' => 4, 'cdk_id' => 42, 'status' => 'draft', 'regency_id' => 1,
            'district_id' => 10, 'pengelola_wisata_id' => 5,
            'columns' => ['area_function', 'creator'],
        ]);

        $this->assertSame([1], $export->query()->pluck('id')->all());
        $this->assertSame(['Fungsi Kawasan', 'Diinput Oleh'], $export->headings());
        $this->assertSame(['Hutan', 'Operator CDK'], $export->map($export->query()->first()));
    }

    public function test_pengunjung_export_uses_filters_and_selected_columns(): void
    {
        foreach ([[1, 42, 2025, 4, 5, 'draft'], [2, 43, 2025, 4, 5, 'draft'],
            [3, 42, 2025, 5, 5, 'draft'], [4, 42, 2025, 4, 5, 'final'],
            [5, 42, 2025, 4, 6, 'draft'], [6, 42, 2024, 4, 5, 'draft']] as
            [$id, $cdk, $year, $month, $manager, $status]) {
            DB::table('pengunjung_wisata')->insert([
                'id' => $id, 'cdk_id' => $cdk, 'year' => $year, 'month' => $month,
                'id_pengelola_wisata' => $manager, 'number_of_visitors' => 12,
                'gross_income' => 100000, 'status' => $status, 'created_by' => 7,
                'created_at' => '2025-04-01', 'updated_at' => '2025-04-01',
            ]);
        }

        $export = new PengunjungWisataExport(2025, [
            'month' => 4, 'cdk_id' => 42, 'status' => 'draft', 'pengelola_wisata_id' => 5,
            'columns' => ['pengelola_wisata', 'number_of_visitors', 'creator'],
        ]);

        $this->assertSame([1], $export->query()->pluck('id')->all());
        $this->assertSame(['Pengelola Wisata', 'Jumlah Pengunjung', 'Diinput Oleh'], $export->headings());
        $this->assertSame(['Pengelola A', 12, 'Operator CDK'], $export->map($export->query()->first()));
    }
}
