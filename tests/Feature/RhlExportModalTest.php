<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Rhl\App\Exports\PenghijauanLingkunganExport;
use Modules\Rhl\App\Exports\ReboisasiPsExport;
use Modules\Rhl\App\Exports\RehabLahanExport;
use Modules\Rhl\App\Exports\RehabManggroveExport;
use Modules\Rhl\App\Exports\RhlTeknisExport;
use Tests\TestCase;

class RhlExportModalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'rhl_export_testing', 'database.connections.rhl_export_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('rhl_export_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->unsignedBigInteger('cdk_id')->nullable();
        });
        foreach (['m_provinces', 'm_regencies', 'm_districts', 'm_villages', 'm_pengelola_ps', 'm_bangunan_kta'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->string('name');
            });
        }
        foreach (['rehab_lahan', 'rehab_manggrove', 'penghijauan_lingkungan', 'reboisasi_ps', 'rhl_teknis'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id(); $table->unsignedBigInteger('cdk_id'); $table->integer('year'); $table->integer('month');
                $table->unsignedBigInteger('province_id')->nullable(); $table->unsignedBigInteger('regency_id');
                $table->unsignedBigInteger('district_id'); $table->unsignedBigInteger('village_id');
                $table->decimal('target_annual')->default(0); $table->decimal('realization')->default(0);
                $table->string('fund_source'); $table->string('status');
                if ($name === 'reboisasi_ps') $table->unsignedBigInteger('pengelola_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable(); $table->timestamps(); $table->softDeletes();
            });
        }
        Schema::create('rhl_teknis_details', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('rhl_teknis_id');
            $table->unsignedBigInteger('bangunan_kta_id'); $table->integer('unit_amount'); $table->timestamps();
        });

        DB::table('users')->insert(['id' => 7, 'name' => 'Operator CDK', 'cdk_id' => 42]);
        foreach (['m_provinces', 'm_regencies', 'm_districts', 'm_villages'] as $name) {
            DB::table($name)->insert(['id' => 1, 'name' => 'Lokasi A']);
        }
        DB::table('m_pengelola_ps')->insert(['id' => 5, 'name' => 'Pengelola A']);
        DB::table('m_bangunan_kta')->insert(['id' => 6, 'name' => 'Dam Penahan']);
    }

    public function test_four_parent_exports_apply_filters_and_selected_columns(): void
    {
        $exports = [
            'rehab_lahan' => RehabLahanExport::class,
            'rehab_manggrove' => RehabManggroveExport::class,
            'penghijauan_lingkungan' => PenghijauanLingkunganExport::class,
            'reboisasi_ps' => ReboisasiPsExport::class,
        ];

        foreach ($exports as $table => $class) {
            $this->insertRhl($table, 1, 42, 2025, 4, 'draft', 1, 'apbn');
            $this->insertRhl($table, 2, 43, 2025, 4, 'draft', 1, 'apbn');
            $this->insertRhl($table, 3, 42, 2025, 5, 'draft', 1, 'apbn');
            $this->insertRhl($table, 4, 42, 2025, 4, 'final', 1, 'apbn');
            $this->insertRhl($table, 5, 42, 2025, 4, 'draft', 2, 'apbn');
            $this->insertRhl($table, 6, 42, 2025, 4, 'draft', 1, 'apbd');
            $this->insertRhl($table, 7, 42, 2024, 4, 'draft', 1, 'apbn');

            $filters = ['month' => 4, 'status' => 'draft', 'cdk_id' => 42,
                'regency_id' => 1, 'district_id' => 1, 'fund_source' => 'apbn',
                'columns' => ['year', 'creator']];
            if ($table === 'reboisasi_ps') $filters['pengelola_id'] = 5;
            $export = new $class(2025, $filters);

            $this->assertSame([1], $export->query()->pluck('id')->all(), $table);
            $this->assertSame(['Tahun', 'Diinput Oleh'], $export->headings(), $table);
            $this->assertSame([2025, 'Operator CDK'], $export->map($export->query()->first()), $table);
        }
    }

    public function test_technical_export_filters_parent_and_detail_and_observes_cdk_scope(): void
    {
        $this->insertRhl('rhl_teknis', 1, 42, 2025, 4, 'draft', 1, 'apbn');
        $this->insertRhl('rhl_teknis', 2, 43, 2025, 4, 'draft', 1, 'apbn');
        $this->insertRhl('rhl_teknis', 3, 42, 2025, 5, 'draft', 1, 'apbn');
        $this->insertRhl('rhl_teknis', 4, 42, 2025, 4, 'final', 1, 'apbn');
        foreach ([1, 2, 3, 4] as $id) {
            DB::table('rhl_teknis_details')->insert(['id' => $id, 'rhl_teknis_id' => $id,
                'bangunan_kta_id' => 6, 'unit_amount' => 3]);
        }

        $export = new RhlTeknisExport(2025, ['month' => 4, 'status' => 'draft', 'cdk_id' => 42,
            'regency_id' => 1, 'district_id' => 1, 'fund_source' => 'apbn',
            'bangunan_kta_id' => 6, 'columns' => ['building', 'unit_amount']]);
        $this->assertSame([1], $export->query()->pluck('rhl_teknis_details.id')->all());
        $this->assertSame(['Jenis Bangunan', 'Jumlah Unit'], $export->headings());
        $this->assertSame(['Dam Penahan', 3], $export->map($export->query()->first()));

        $user = new User();
        $user->id = 7;
        $user->cdk_id = 42;
        $this->actingAs($user);
        $allVisible = new RhlTeknisExport(2025, ['status' => 'all']);
        $this->assertSame([4, 1, 3], $allVisible->query()->pluck('rhl_teknis_details.id')->all());
    }

    private function insertRhl(string $table, int $id, int $cdk, int $year, int $month, string $status, int $regency, string $fund): void
    {
        $row = ['id' => $id, 'cdk_id' => $cdk, 'year' => $year, 'month' => $month, 'status' => $status,
            'province_id' => 1, 'regency_id' => $regency, 'district_id' => 1, 'village_id' => 1,
            'target_annual' => 10, 'realization' => 5, 'fund_source' => $fund, 'created_by' => 7,
            'created_at' => '2025-04-01', 'updated_at' => '2025-04-01'];
        if ($table === 'reboisasi_ps') $row['pengelola_id'] = 5;
        DB::table($table)->insert($row);
    }
}
