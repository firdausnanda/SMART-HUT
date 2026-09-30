<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Rhl\App\Services\Imports\Processors\RehabLahanProcessor;
use Tests\TestCase;

class RehabLahanImportModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'rhl_import_testing', 'database.connections.rhl_import_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('rhl_import_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
        });
        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->unsignedBigInteger('user_id');
            $table->string('module_name'); $table->string('filename');
            $table->string('status'); $table->unsignedInteger('imported_count')->default(0);
            $table->text('error_message')->nullable(); $table->timestamps();
        });
        Schema::create('import_staging_rows', function (Blueprint $table) {
            $table->id(); $table->uuid('import_batch_id'); $table->integer('row_number');
            $table->json('data_payload'); $table->string('status'); $table->timestamps();
        });
        Schema::create('m_regencies', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('province_id'); $table->string('name');
        });
        Schema::create('m_districts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('regency_id'); $table->string('name');
        });
        Schema::create('m_villages', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('district_id'); $table->string('name');
        });
        Schema::create('rehab_lahan', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('province_id'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id'); $table->unsignedBigInteger('village_id');
            $table->decimal('target_annual', 15, 2); $table->decimal('realization', 15, 2);
            $table->string('fund_source'); $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id(); $table->string('log_name')->nullable(); $table->text('description');
            $table->string('subject_type')->nullable(); $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('event')->nullable(); $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable(); $table->timestamps();
        });

        DB::table('users')->insert(['id' => 7, 'cdk_id' => 42]);
        DB::table('m_regencies')->insert(['id' => 1, 'province_id' => 35, 'name' => 'Kabupaten A']);
        DB::table('m_districts')->insert(['id' => 2, 'regency_id' => 1, 'name' => 'Kecamatan B']);
        DB::table('m_villages')->insert(['id' => 3, 'district_id' => 2, 'name' => 'Desa C']);
    }

    public function test_existing_batch_is_processed_by_the_module_processor(): void
    {
        $batch = ImportBatch::create([
            'user_id' => 7, 'module_name' => 'rehab-lahan',
            'filename' => 'lama.csv', 'status' => 'processing',
        ]);
        $batch->stagingRows()->create([
            'row_number' => 2, 'status' => 'valid',
            'data_payload' => [
                'tahun' => 2026, 'bulan_angka' => 9,
                'nama_kabupaten' => 'Kabupaten A', 'nama_kecamatan' => 'Kecamatan B',
                'nama_desa' => 'Desa C', 'target_tahunan_ha' => 2,
                'realisasi_ha' => '1,5', 'sumber_dana' => 'APBD',
            ],
        ]);

        $resolver = new \ReflectionMethod(ProcessImportBatch::class, 'resolveProcessor');
        $this->assertSame(RehabLahanProcessor::class, $resolver->invoke(new ProcessImportBatch($batch->id), 'rehab-lahan'));

        (new ProcessImportBatch($batch->id))->handle();

        $this->assertDatabaseHas('import_batches', ['id' => $batch->id, 'status' => 'completed', 'imported_count' => 1]);
        $this->assertDatabaseHas('rehab_lahan', [
            'cdk_id' => 42, 'year' => 2026, 'month' => 9,
            'target_annual' => 2, 'realization' => 1.5,
            'fund_source' => 'apbd', 'status' => 'draft',
        ]);
    }
}
