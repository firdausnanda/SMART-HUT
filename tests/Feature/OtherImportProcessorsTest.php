<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Services\Imports\Processors\KupsProcessor;
use App\Services\Imports\Processors\NilaiEkonomiProcessor;
use App\Services\Imports\Processors\SkpsProcessor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OtherImportProcessorsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'other_import_testing', 'database.connections.other_import_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('other_import_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
        });
        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->unsignedBigInteger('user_id');
            $table->string('module_name'); $table->string('filename');
            $table->string('status'); $table->unsignedInteger('imported_count')->default(0);
            $table->timestamps();
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
        Schema::create('m_skema_perhutanan_sosial', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        Schema::create('m_commodities', function (Blueprint $table) {
            $table->id(); $table->string('name');
            $table->boolean('is_nilai_transaksi_ekonomi')->default(false);
            $table->softDeletes();
        });
        Schema::create('kups', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
            $table->unsignedBigInteger('province_id'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id'); $table->string('nama_kups');
            $table->string('category')->nullable(); $table->string('commodity')->nullable();
            $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable(); $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('skps', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
            $table->unsignedBigInteger('province_id'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id'); $table->string('id_skema_perhutanan_sosial');
            $table->string('nama_kelompok'); $table->string('potential');
            $table->string('ps_area'); $table->string('number_of_kk');
            $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable(); $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('nilai_ekonomi', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('province_id'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id'); $table->string('nama_kelompok');
            $table->decimal('total_transaction_value', 19, 2)->default(0);
            $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable(); $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('nilai_ekonomi_details', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('nilai_ekonomi_id');
            $table->unsignedBigInteger('commodity_id'); $table->decimal('production_volume', 15, 2);
            $table->string('satuan'); $table->decimal('transaction_value', 19, 2);
            $table->timestamps();
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
        DB::table('m_skema_perhutanan_sosial')->insert(['id' => 3, 'name' => 'Hutan Desa']);
        DB::table('m_commodities')->insert([
            ['id' => 10, 'name' => 'Kopi'], ['id' => 11, 'name' => 'Madu'],
        ]);
    }

    private function batch(string $module, array $rows): ImportBatch
    {
        $batch = ImportBatch::create([
            'user_id' => 7, 'module_name' => $module,
            'filename' => 'test.xlsx', 'status' => 'processing',
        ]);
        foreach ($rows as $index => $row) {
            $batch->stagingRows()->create([
                'row_number' => $index + 2, 'status' => 'valid', 'data_payload' => $row,
            ]);
        }
        return $batch;
    }

    public function test_kups_import_sets_cdk_from_batch_owner(): void
    {
        $batch = $this->batch('kups', [[
            'nama_kabupatenkota' => 'Kabupaten A', 'nama_kecamatan' => 'Kecamatan B',
            'nama_kups' => 'KUPS A', 'kategori' => 'Biru', 'komoditas' => 'Kopi',
        ]]);

        $this->assertSame(1, app(KupsProcessor::class)->process($batch));
        $this->assertSame(42, DB::table('kups')->value('cdk_id'));
    }

    public function test_skps_import_saves_valid_staging_row(): void
    {
        $batch = $this->batch('skps', [[
            'nama_kabupatenkota' => 'Kabupaten A', 'nama_kecamatan' => 'Kecamatan B',
            'nama_kelompok' => 'KTH Harapan', 'nama_skema_perhutanan_sosial' => 'Hutan Desa',
            'potensi' => 'Madu', 'luas_ps_ha' => 12.5, 'jumlah_kk' => 8,
        ]]);

        $this->assertSame(1, app(SkpsProcessor::class)->process($batch));
        $record = DB::table('skps')->first();
        $this->assertNotNull($record);
        $this->assertSame(42, $record->cdk_id);
        $this->assertSame(7, $record->created_by);
        $this->assertSame('3', $record->id_skema_perhutanan_sosial);
        $this->assertSame('Madu', $record->potential);
        $this->assertSame(12.5, (float) $record->ps_area);
        $this->assertSame('8', $record->number_of_kk);
    }

    public function test_nilai_ekonomi_import_groups_details_by_group_location_and_period(): void
    {
        $base = [
            'tahun' => 2026, 'bulan_1_12' => 5,
            'nama_kabupaten' => 'Kabupaten A', 'nama_kecamatan' => 'Kecamatan B',
            'nama_kelompok' => 'KTH Harapan', 'komoditas' => 'Kopi',
            'volume_produksi' => '2', 'satuan' => 'kg', 'nilai_transaksi_rp' => '100000',
        ];
        $batch = $this->batch('nilai-ekonomi', [
            $base,
            array_merge($base, ['nama_kelompok' => ' KTH Harapan ', 'komoditas' => 'Madu', 'nilai_transaksi_rp' => '250000']),
            array_merge($base, ['bulan_1_12' => 6]),
        ]);

        $this->assertSame(3, app(NilaiEkonomiProcessor::class)->process($batch));
        $parents = DB::table('nilai_ekonomi')->orderBy('id')->get();
        $this->assertCount(2, $parents);
        $this->assertSame(42, $parents[0]->cdk_id);
        $this->assertSame(2, DB::table('nilai_ekonomi_details')->where('nilai_ekonomi_id', $parents[0]->id)->count());
        $this->assertSame(350000, (int) $parents[0]->total_transaction_value);
    }
}
