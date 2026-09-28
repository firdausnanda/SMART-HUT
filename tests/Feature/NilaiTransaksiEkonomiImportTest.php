<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\User;
use App\Services\Imports\Processors\NilaiTransaksiEkonomiProcessor;
use App\Services\Imports\NilaiTransaksiEkonomiRepairService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NilaiTransaksiEkonomiImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'transaction_import_testing', 'database.connections.transaction_import_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('transaction_import_testing');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cdk_id')->nullable();
        });
        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('module_name');
            $table->string('filename');
            $table->string('status');
            $table->unsignedInteger('imported_count')->default(0);
            $table->timestamps();
        });
        Schema::create('import_staging_rows', function (Blueprint $table) {
            $table->id();
            $table->uuid('import_batch_id');
            $table->integer('row_number');
            $table->json('data_payload');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('m_regencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('province_id');
            $table->string('name');
        });
        Schema::create('m_districts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regency_id');
            $table->string('name');
        });
        Schema::create('m_villages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('district_id');
            $table->string('name');
        });
        Schema::create('m_commodities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_nilai_transaksi_ekonomi')->default(true);
            $table->softDeletes();
        });
        Schema::create('nilai_transaksi_ekonomi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('year');
            $table->integer('month');
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('village_id');
            $table->string('nama_kth');
            $table->decimal('total_nilai_transaksi', 20, 2)->default(0);
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('nilai_transaksi_ekonomi_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nilai_transaksi_ekonomi_id');
            $table->unsignedBigInteger('commodity_id');
            $table->decimal('volume_produksi', 15, 2);
            $table->string('satuan');
            $table->decimal('nilai_transaksi', 20, 2);
            $table->timestamps();
        });
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('event')->nullable();
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert(['id' => 7, 'cdk_id' => 42]);
        DB::table('m_regencies')->insert(['id' => 1, 'province_id' => 35, 'name' => 'Kabupaten A']);
        DB::table('m_districts')->insert(['id' => 2, 'regency_id' => 1, 'name' => 'Kecamatan B']);
        DB::table('m_villages')->insert([
            ['id' => 3, 'district_id' => 2, 'name' => 'Desa C'],
            ['id' => 4, 'district_id' => 2, 'name' => 'Desa D'],
        ]);
        DB::table('m_commodities')->insert([
            ['id' => 10, 'name' => 'Kopi'],
            ['id' => 11, 'name' => 'Madu'],
        ]);
    }

    private function batch(array $rows): ImportBatch
    {
        $batch = ImportBatch::create([
            'user_id' => 7,
            'module_name' => 'nilai-transaksi-ekonomi',
            'filename' => 'test.xlsx',
            'status' => 'processing',
        ]);

        foreach ($rows as $index => $overrides) {
            $batch->stagingRows()->create([
                'row_number' => $index + 2,
                'status' => 'valid',
                'data_payload' => array_merge([
                    'tahun' => 2026,
                    'bulan_1_12' => 5,
                    'nama_kabupaten' => 'Kabupaten A',
                    'nama_kecamatan' => 'Kecamatan B',
                    'nama_desa' => 'Desa C',
                    'nama_kth' => 'KTH Harapan',
                    'komoditas' => 'Kopi',
                    'volume_produksi' => '2',
                    'satuan' => 'kg',
                    'nilai_transaksi_rp' => '100000',
                ], $overrides),
            ]);
        }

        return $batch;
    }

    public function test_same_kth_location_and_period_share_one_parent_with_importers_cdk(): void
    {
        $batch = $this->batch([
            [],
            ['nama_kth' => ' KTH Harapan ', 'komoditas' => 'Madu', 'nilai_transaksi_rp' => '250000'],
            ['nama_desa' => 'Desa D', 'komoditas' => 'Madu', 'nilai_transaksi_rp' => '300000'],
            ['bulan_1_12' => 6, 'komoditas' => 'Madu', 'nilai_transaksi_rp' => '400000'],
            ['nama_kth' => 'KTH Sejahtera', 'komoditas' => 'Madu', 'nilai_transaksi_rp' => '500000'],
            ['tahun' => 2025, 'komoditas' => 'Madu', 'nilai_transaksi_rp' => '600000'],
        ]);

        $this->assertSame(6, app(NilaiTransaksiEkonomiProcessor::class)->process($batch));

        $parents = DB::table('nilai_transaksi_ekonomi')->orderBy('id')->get();
        $this->assertCount(5, $parents);
        $this->assertSame(42, $parents[0]->cdk_id);
        $this->assertSame(7, $parents[0]->created_by);
        $this->assertSame('350000', (string) (int) $parents[0]->total_nilai_transaksi);
        $this->assertSame(2, DB::table('nilai_transaksi_ekonomi_details')
            ->where('nilai_transaksi_ekonomi_id', $parents[0]->id)->count());
    }

    public function test_matching_rows_across_staging_chunks_stay_grouped(): void
    {
        $rows = array_fill(0, 201, []);
        $batch = $this->batch($rows);

        $this->assertSame(201, app(NilaiTransaksiEkonomiProcessor::class)->process($batch));
        $this->assertSame(1, DB::table('nilai_transaksi_ekonomi')->count());
        $this->assertSame(201, DB::table('nilai_transaksi_ekonomi_details')->count());
        $this->assertSame('20100000', (string) (int) DB::table('nilai_transaksi_ekonomi')->value('total_nilai_transaksi'));
    }

    private function existingTransaction(array $overrides, int $value): int
    {
        $id = DB::table('nilai_transaksi_ekonomi')->insertGetId(array_merge([
            'cdk_id' => null, 'year' => 2026, 'month' => 5,
            'province_id' => 35, 'regency_id' => 1, 'district_id' => 2, 'village_id' => 3,
            'nama_kth' => 'KTH Harapan', 'total_nilai_transaksi' => 999,
            'status' => 'draft', 'created_by' => 7,
            'created_at' => now(), 'updated_at' => now(),
        ], $overrides));
        DB::table('nilai_transaksi_ekonomi_details')->insert([
            'nilai_transaksi_ekonomi_id' => $id, 'commodity_id' => 10,
            'volume_produksi' => 1, 'satuan' => 'kg', 'nilai_transaksi' => $value,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $id;
    }

    public function test_repair_route_backfills_cdk_and_merges_only_matching_drafts_in_own_cdk(): void
    {
        DB::table('users')->insert(['id' => 8, 'cdk_id' => 43]);
        $first = $this->existingTransaction([], 100);
        $duplicate = $this->existingTransaction(['nama_kth' => '  kth   harapan  '], 250);
        $otherCdk = $this->existingTransaction(['cdk_id' => 43, 'created_by' => 8], 300);
        $otherMonth = $this->existingTransaction(['month' => 6], 400);
        $otherVillage = $this->existingTransaction(['village_id' => 4], 450);
        $approved = $this->existingTransaction(['status' => 'final'], 500);

        $user = new User(['cdk_id' => 42]);
        $user->id = 7;
        $this->actingAs($user)->withoutMiddleware();

        $this->getJson('/nilai-transaksi-ekonomi/repair-imported-data/preview')
            ->assertOk()
            ->assertJsonPath('merged_groups', 1)
            ->assertJsonPath('merged_records', 1)
            ->assertJsonPath('cdk_filled', 4)
            ->assertJsonPath('groups.0.keep_id', $first);
        $this->assertNull(DB::table('nilai_transaksi_ekonomi')->where('id', $duplicate)->value('deleted_at'));

        $this->postJson('/nilai-transaksi-ekonomi/repair-imported-data')
            ->assertOk()
            ->assertJsonPath('merged_groups', 1)
            ->assertJsonPath('merged_records', 1)
            ->assertJsonPath('cdk_filled', 4);

        $this->assertSame(42, DB::table('nilai_transaksi_ekonomi')->where('id', $first)->value('cdk_id'));
        $this->assertNotNull(DB::table('nilai_transaksi_ekonomi')->where('id', $duplicate)->value('deleted_at'));
        $this->assertSame(2, DB::table('nilai_transaksi_ekonomi_details')
            ->where('nilai_transaksi_ekonomi_id', $first)->count());
        $this->assertSame(350, (int) DB::table('nilai_transaksi_ekonomi')->where('id', $first)->value('total_nilai_transaksi'));
        $this->assertNull(DB::table('nilai_transaksi_ekonomi')->where('id', $otherCdk)->value('deleted_at'));
        $this->assertNull(DB::table('nilai_transaksi_ekonomi')->where('id', $otherMonth)->value('deleted_at'));
        $this->assertSame(42, DB::table('nilai_transaksi_ekonomi')->where('id', $otherMonth)->value('cdk_id'));
        $this->assertNull(DB::table('nilai_transaksi_ekonomi')->where('id', $otherVillage)->value('deleted_at'));
        $this->assertNull(DB::table('nilai_transaksi_ekonomi')->where('id', $approved)->value('deleted_at'));
        $this->assertNull(DB::table('nilai_transaksi_ekonomi')->where('id', $approved)->value('cdk_id'));

        $this->postJson('/nilai-transaksi-ekonomi/repair-imported-data')
            ->assertOk()->assertJsonPath('merged_groups', 0)->assertJsonPath('cdk_filled', 0);
    }

    public function test_repair_leaves_records_without_a_known_cdk_separate(): void
    {
        $first = $this->existingTransaction(['created_by' => 98], 100);
        $second = $this->existingTransaction(['created_by' => 99], 250);
        $repair = app(NilaiTransaksiEkonomiRepairService::class);

        $this->assertSame(2, $repair->preview(null)['missing_cdk']);
        $this->assertSame(0, $repair->repair(null, 7)['merged_groups']);
        $this->assertNull(DB::table('nilai_transaksi_ekonomi')->where('id', $first)->value('deleted_at'));
        $this->assertNull(DB::table('nilai_transaksi_ekonomi')->where('id', $second)->value('deleted_at'));
    }
}
