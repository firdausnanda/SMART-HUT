<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\UsesIsolatedForestryDatabase;
use Tests\TestCase;

class BinaUsahaModuleFlowTest extends TestCase
{
    use UsesIsolatedForestryDatabase { setUp as setUpForestryDatabase; }

    protected function setUp(): void
    {
        $this->setUpForestryDatabase();

        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->unsignedBigInteger('user_id');
            $table->string('module_name'); $table->string('filename');
            $table->string('status'); $table->unsignedInteger('imported_count')->default(0);
            $table->text('error_message')->nullable(); $table->timestamps();
        });
        Schema::create('import_staging_rows', function (Blueprint $table) {
            $table->id(); $table->uuid('import_batch_id'); $table->integer('row_number');
            $table->json('data_payload'); $table->string('status');
            $table->text('validation_errors')->nullable(); $table->timestamps();
        });
        Schema::create('m_kayu', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps();
        });
        Schema::create('m_bukan_kayu', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps();
        });
        Schema::create('m_pengelola_hutan', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps();
        });
        Schema::create('hasil_hutan_kayu', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('province_id'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('pengelola_hutan_id')->nullable();
            $table->unsignedBigInteger('pengelola_wisata_id')->nullable();
            $table->string('forest_type'); $table->decimal('volume_target', 15, 2);
            $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('hasil_hutan_kayu_details', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('hasil_hutan_kayu_id');
            $table->unsignedBigInteger('kayu_id'); $table->decimal('volume_realization', 15, 2);
            $table->timestamps();
        });
        Schema::create('hasil_hutan_bukan_kayu', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('cdk_id')->nullable();
            $table->integer('year'); $table->integer('month');
            $table->unsignedBigInteger('province_id'); $table->unsignedBigInteger('regency_id');
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('pengelola_hutan_id')->nullable();
            $table->unsignedBigInteger('pengelola_wisata_id')->nullable();
            $table->string('forest_type'); $table->decimal('volume_target', 15, 2);
            $table->string('status'); $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('hasil_hutan_bukan_kayu_details', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('hasil_hutan_bukan_kayu_id');
            $table->unsignedBigInteger('bukan_kayu_id');
            $table->decimal('annual_volume_realization', 15, 2);
            $table->string('unit'); $table->timestamps();
        });

        DB::table('m_kayu')->insert(['id' => 1, 'name' => 'Jati']);
        DB::table('m_bukan_kayu')->insert(['id' => 1, 'name' => 'Bambu']);
        DB::table('m_pengelola_hutan')->insert(['id' => 1, 'name' => 'Perhutani']);

        foreach (['produksi-hutan-negara', 'produksi-perhutanan-sosial', 'produksi-hutan-rakyat'] as $prefix) {
            foreach (['view', 'create', 'import', 'export'] as $action) {
                Permission::firstOrCreate(['name' => "$prefix.$action", 'guard_name' => 'web']);
            }
        }
        foreach (['pbphh.import', 'pbphh.export', 'realisasi-pnbp.import', 'realisasi-pnbp.export'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function operator(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_forest_type_permission_is_enforced_inside_the_module_controller(): void
    {
        $this->get(route('hasil-hutan-kayu.template'))->assertRedirect(route('login'));
        $this->actingAs($this->operator(['produksi-hutan-negara.view', 'produksi-hutan-negara.create']));

        $this->get(route('hasil-hutan-kayu.template', ['forest_type' => 'Hutan Negara']))->assertOk();
        $this->get(route('hasil-hutan-kayu.template', ['forest_type' => 'Perhutanan Sosial']))->assertForbidden();
    }

    public function test_all_four_templates_download_from_the_module(): void
    {
        $this->actingAs($this->operator([
            'produksi-hutan-negara.view', 'produksi-hutan-negara.create',
            'pbphh.create', 'realisasi-pnbp.create',
        ]));
        $templates = [
            'hasil-hutan-kayu' => 'template_import_hasil_hutan_kayu.xlsx',
            'hasil-hutan-bukan-kayu' => 'template_import_hasil_hutan_bukan_kayu.xlsx',
            'pbphh' => 'template_import_pbphh.xlsx',
            'realisasi-pnbp' => 'template_import_realisasi_pnbp.xlsx',
        ];

        foreach ($templates as $feature => $filename) {
            $response = $this->get(route("$feature.template", ['forest_type' => 'Hutan Negara']));
            $response->assertOk();
            $this->assertStringContainsString($filename, $response->headers->get('content-disposition'));
        }
    }

    public function test_forest_preview_and_commit_keep_legacy_batch_names(): void
    {
        Queue::fake();
        $this->actingAs($this->operator(['produksi-hutan-negara.import']));
        $files = [
            'hasil-hutan-kayu' => [
                'hasil-hutan-kayu|Hutan Negara',
                "Tahun,Bulan (Angka),Nama Kabupaten,Total Target (m3)\n2026,9,KABUPATEN TRENGGALEK,2",
            ],
            'hasil-hutan-bukan-kayu' => [
                'hhbk|Hutan Negara',
                "Tahun,Bulan (Angka),Nama Kabupaten,Total Target,Nama Pengelola\n2026,9,KABUPATEN TRENGGALEK,2,Perhutani",
            ],
        ];

        foreach ($files as $feature => [$moduleName, $csv]) {
            $this->post(route("$feature.preview-import"), [
                'forest_type' => 'Hutan Negara',
                'file' => UploadedFile::fake()->createWithContent("$feature.csv", $csv),
            ])->assertRedirect();

            $batch = ImportBatch::where('module_name', $moduleName)->firstOrFail();
            $this->assertSame('valid', $batch->stagingRows()->firstOrFail()->status);
            $this->get(route("$feature.show-preview", $batch))->assertOk();
            $this->post(route("$feature.commit-import", $batch))->assertRedirect();
            $this->assertDatabaseHas('import_batches', ['id' => $batch->id, 'status' => 'processing']);
            Queue::assertPushed(ProcessImportBatch::class, fn ($job) => $job->batchId === $batch->id);
        }
    }

    public function test_existing_pbphh_and_pnbp_batches_are_processed_after_migration(): void
    {
        $user = User::factory()->create(['cdk_id' => 42]);
        $rows = [
            'pbphh' => [
                'nama_kabupatenkota' => 'TRENGGALEK', 'nama_kecamatan' => 'Kecamatan Uji',
                'nama_industri' => 'Industri Uji', 'nomor_izin' => 'IZIN-123',
                'nilai_investasi' => 100000, 'jumlah_tenaga_kerja' => 5,
                'kondisi_saat_ini' => 'Aktif', 'jenis_produksi_kapasitas' => 'Produksi Uji (12 m3)',
            ],
            'realisasi-pnbp' => [
                'tahun' => 2026, 'bulan_angka_1_12' => 9,
                'nama_kabupatenkota' => 'TRENGGALEK', 'nama_pengelola_wisata' => 'Pengelola Uji',
                'jenis_hasil_hutan' => 'Kayu', 'target_pnbp' => 100000,
                'realisasi_pnbp' => 50000,
            ],
        ];

        foreach ($rows as $moduleName => $row) {
            $batch = ImportBatch::create([
                'user_id' => $user->id, 'module_name' => $moduleName,
                'filename' => 'batch-lama.csv', 'status' => 'processing',
            ]);
            $batch->stagingRows()->create([
                'row_number' => 2, 'status' => 'valid', 'data_payload' => $row,
            ]);

            (new ProcessImportBatch($batch->id))->handle();
            $this->assertDatabaseHas('import_batches', [
                'id' => $batch->id, 'status' => 'completed', 'imported_count' => 1,
            ]);
        }

        $this->assertDatabaseHas('pbphh', ['number' => 'IZIN-123', 'cdk_id' => 42, 'name' => 'Industri Uji']);
        $this->assertDatabaseHas('pbphh_jenis_produksi', ['jenis_produksi_id' => 1, 'kapasitas_ijin' => 12]);
        $this->assertDatabaseHas('realisasi_pnbp', [
            'year' => 2026, 'month' => 9, 'cdk_id' => 42,
            'pnbp_realization' => 50000, 'id_pengelola_wisata' => 1,
        ]);
    }

    public function test_existing_forest_batches_keep_their_type_and_detail_rows(): void
    {
        $user = User::factory()->create(['cdk_id' => 42]);
        $rows = [
            'hasil-hutan-kayu|Hutan Negara' => [
                'tahun' => 2026, 'bulan_angka' => 9, 'nama_kabupaten' => 'TRENGGALEK',
                'nama_pengelola_hutan' => 'Perhutani', 'total_target_m3' => 2,
                'jati_realisasi' => 1.5,
            ],
            'hhbk|Perhutanan Sosial' => [
                'tahun' => 2026, 'bulan_angka' => 9, 'nama_kabupaten' => 'TRENGGALEK',
                'nama_pengelola_wisata' => 'Pengelola Uji', 'total_target' => 4,
                'bambu_realisasi' => 2, 'bambu_satuan' => 'kg',
            ],
        ];

        foreach ($rows as $moduleName => $row) {
            $batch = ImportBatch::create([
                'user_id' => $user->id, 'module_name' => $moduleName,
                'filename' => 'batch-lama.csv', 'status' => 'processing',
            ]);
            $batch->stagingRows()->create([
                'row_number' => 2, 'status' => 'valid', 'data_payload' => $row,
            ]);

            (new ProcessImportBatch($batch->id))->handle();
            $this->assertDatabaseHas('import_batches', [
                'id' => $batch->id, 'status' => 'completed', 'imported_count' => 1,
            ]);
        }

        $this->assertDatabaseHas('hasil_hutan_kayu', [
            'forest_type' => 'Hutan Negara', 'cdk_id' => 42,
            'pengelola_hutan_id' => 1, 'volume_target' => 2,
        ]);
        $this->assertDatabaseHas('hasil_hutan_kayu_details', [
            'kayu_id' => 1, 'volume_realization' => 1.5,
        ]);
        $this->assertDatabaseHas('hasil_hutan_bukan_kayu', [
            'forest_type' => 'Perhutanan Sosial', 'cdk_id' => 42,
            'pengelola_wisata_id' => 1, 'volume_target' => 4,
        ]);
        $this->assertDatabaseHas('hasil_hutan_bukan_kayu_details', [
            'bukan_kayu_id' => 1, 'annual_volume_realization' => 2, 'unit' => 'kg',
        ]);
    }
}
