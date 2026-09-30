<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Perlindungan\App\Services\Imports\Processors\PengunjungWisataProcessor;
use Tests\TestCase;

class PengunjungWisataImportModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'pengunjung_import_testing', 'database.connections.pengunjung_import_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('pengunjung_import_testing');

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
        Schema::create('m_pengelola_wisata', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        Schema::create('pengunjung_wisata', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('id_pengelola_wisata')->nullable();
            $table->integer('number_of_visitors'); $table->decimal('gross_income', 15, 2);
            $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
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
        DB::table('m_pengelola_wisata')->insert(['id' => 4, 'name' => 'Pengelola D']);
    }

    public function test_existing_batch_is_processed_by_the_module_processor(): void
    {
        $batch = ImportBatch::create([
            'user_id' => 7, 'module_name' => 'pengunjung-wisata',
            'filename' => 'lama.csv', 'status' => 'processing',
        ]);
        $batch->stagingRows()->create([
            'row_number' => 2, 'status' => 'valid',
            'data_payload' => [
                'tahun' => 2026, 'bulan_angka_1_12' => 9,
                'nama_pengelola_wisata' => 'Pengelola D',
                'jumlah_pengunjung' => 12, 'pendapatan_bruto_rp' => 150000,
            ],
        ]);

        $resolver = new \ReflectionMethod(ProcessImportBatch::class, 'resolveProcessor');
        $this->assertSame(PengunjungWisataProcessor::class, $resolver->invoke(new ProcessImportBatch($batch->id), 'pengunjung-wisata'));

        (new ProcessImportBatch($batch->id))->handle();

        $this->assertDatabaseHas('import_batches', ['id' => $batch->id, 'status' => 'completed', 'imported_count' => 1]);
        $this->assertDatabaseHas('pengunjung_wisata', [
            'cdk_id' => 42, 'year' => 2026, 'month' => 9,
            'id_pengelola_wisata' => 4, 'number_of_visitors' => 12,
            'gross_income' => 150000, 'status' => 'draft',
        ]);
    }
}
