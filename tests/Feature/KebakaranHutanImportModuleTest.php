<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Perlindungan\App\Services\Imports\Processors\KebakaranHutanProcessor;
use Tests\TestCase;

class KebakaranHutanImportModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'kebakaran_import_testing', 'database.connections.kebakaran_import_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('kebakaran_import_testing');

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
        Schema::create('m_pengelola_wisata', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        Schema::create('kebakaran_hutan', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('province_id'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id'); $table->unsignedBigInteger('village_id');
            $table->unsignedBigInteger('id_pengelola_wisata')->nullable();
            $table->string('area_function')->nullable(); $table->integer('number_of_fires');
            $table->string('fire_area'); $table->string('status');
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
        DB::table('m_pengelola_wisata')->insert(['id' => 4, 'name' => 'Pengelola D']);
    }

    public function test_existing_batch_is_processed_by_the_module_processor(): void
    {
        $batch = ImportBatch::create([
            'user_id' => 7, 'module_name' => 'kebakaran-hutan',
            'filename' => 'lama.csv', 'status' => 'processing',
        ]);
        $batch->stagingRows()->create([
            'row_number' => 2, 'status' => 'valid',
            'data_payload' => [
                'tahun' => 2026, 'bulan_angka_1_12' => 9,
                'nama_kabupatenkota' => 'Kabupaten A', 'nama_kecamatan' => 'Kecamatan B',
                'nama_desa' => 'Desa C', 'nama_pengelola_wisata' => 'Pengelola D',
                'fungsi_kawasan' => 'Lindung', 'jumlah_kejadian' => 2,
                'luas_kebakaran_ha' => '1,5',
            ],
        ]);

        $resolver = new \ReflectionMethod(ProcessImportBatch::class, 'resolveProcessor');
        $this->assertSame(KebakaranHutanProcessor::class, $resolver->invoke(new ProcessImportBatch($batch->id), 'kebakaran-hutan'));

        (new ProcessImportBatch($batch->id))->handle();

        $this->assertDatabaseHas('import_batches', ['id' => $batch->id, 'status' => 'completed', 'imported_count' => 1]);
        $this->assertDatabaseHas('kebakaran_hutan', [
            'cdk_id' => 42, 'year' => 2026, 'month' => 9,
            'number_of_fires' => 2, 'fire_area' => '1.5', 'status' => 'draft',
        ]);
    }
}
