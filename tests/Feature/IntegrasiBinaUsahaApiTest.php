<?php

namespace Tests\Feature;

use App\Models\IntegrationClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesIsolatedUserDatabase;
use Tests\TestCase;

class IntegrasiBinaUsahaApiTest extends TestCase
{
    use UsesIsolatedUserDatabase { setUp as setUpUserDatabase; }

    private int $trenggalek;
    private int $malang;

    protected function setUp(): void
    {
        $this->setUpUserDatabase();

        Schema::create('cdks', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->timestamps();
        });
        foreach (['m_provinces' => null, 'm_regencies' => 'province_id', 'm_districts' => 'regency_id'] as $name => $parent) {
            Schema::create($name, function (Blueprint $table) use ($parent) {
                $table->string('id')->primary();
                if ($parent) {
                    $table->string($parent);
                }
                $table->string('name');
            });
        }
        foreach (['m_pengelola_hutan', 'm_pengelola_wisata', 'm_kayu', 'm_bukan_kayu', 'm_jenis_produksi'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
        $workflow = function (Blueprint $t) {
            $t->string('status')->default('draft');
            $t->timestamp('approved_by_kasi_at')->nullable();
            $t->timestamp('approved_by_cdk_at')->nullable();
            $t->text('rejection_note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->unsignedBigInteger('deleted_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unsignedBigInteger('cdk_id')->nullable();
        };
        foreach (['hasil_hutan_kayu', 'hasil_hutan_bukan_kayu'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($workflow) {
                $t->id();
                $t->integer('year');
                $t->tinyInteger('month');
                $t->unsignedBigInteger('province_id');
                $t->unsignedBigInteger('regency_id');
                $t->unsignedBigInteger('district_id')->nullable();
                $t->unsignedBigInteger('pengelola_hutan_id')->nullable();
                $t->unsignedBigInteger('pengelola_wisata_id')->nullable();
                $t->string('forest_type');
                $t->decimal('volume_target', 15, 2)->default(0);
                $workflow($t);
            });
        }
        Schema::create('hasil_hutan_kayu_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('hasil_hutan_kayu_id');
            $t->unsignedBigInteger('kayu_id');
            $t->decimal('volume_realization', 15, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('hasil_hutan_bukan_kayu_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('hasil_hutan_bukan_kayu_id');
            $t->unsignedBigInteger('bukan_kayu_id');
            $t->decimal('annual_volume_realization', 10, 2)->default(0);
            $t->string('unit')->default('kg');
            $t->timestamps();
        });
        Schema::create('pbphh', function (Blueprint $t) use ($workflow) {
            $t->id();
            $t->unsignedBigInteger('province_id');
            $t->unsignedBigInteger('regency_id');
            $t->unsignedBigInteger('district_id');
            $t->string('name');
            $t->string('number');
            $t->string('investment_value');
            $t->integer('number_of_workers');
            $t->boolean('present_condition');
            $workflow($t);
        });
        Schema::create('pbphh_jenis_produksi', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('pbphh_id');
            $t->unsignedBigInteger('jenis_produksi_id');
            $t->decimal('kapasitas_ijin', 15, 2)->nullable();
            $t->timestamps();
        });
        Schema::create('realisasi_pnbp', function (Blueprint $t) use ($workflow) {
            $t->id();
            $t->integer('year');
            $t->tinyInteger('month');
            $t->unsignedBigInteger('province_id');
            $t->unsignedBigInteger('regency_id');
            $t->string('types_of_forest_products');
            $t->string('id_pengelola_wisata');
            $t->string('pnbp_target');
            $t->string('pnbp_realization');
            $workflow($t);
        });
        (require base_path('vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require base_path('database/migrations/2026_11_01_000001_create_integration_clients_table.php'))->up();

        $this->trenggalek = DB::table('cdks')->insertGetId(['kode' => 'CDK Wilayah Trenggalek', 'nama' => 'CDK Wilayah Trenggalek']);
        $this->malang = DB::table('cdks')->insertGetId(['kode' => 'CDK Wilayah Malang', 'nama' => 'CDK Wilayah Malang']);
        DB::table('m_provinces')->insert(['id' => '35', 'name' => 'JAWA TIMUR']);
        DB::table('m_regencies')->insert([
            ['id' => '3503', 'province_id' => '35', 'name' => 'KABUPATEN TRENGGALEK'],
            ['id' => '3506', 'province_id' => '35', 'name' => 'KABUPATEN KEDIRI'],
        ]);
        DB::table('m_districts')->insert(['id' => '3503130', 'regency_id' => '3503', 'name' => 'BENDUNGAN']);
        DB::table('m_pengelola_hutan')->insert(['id' => 5, 'name' => 'BKPH Trenggalek']);
        DB::table('m_pengelola_wisata')->insert(['id' => 4, 'name' => 'Lancar Jaya (Kediri)']);
        DB::table('m_kayu')->insert([['id' => 1, 'name' => 'Jati'], ['id' => 2, 'name' => 'Mahoni']]);
        DB::table('m_bukan_kayu')->insert([['id' => 27, 'name' => 'Bambu'], ['id' => 4, 'name' => 'Empon - Empon']]);
        DB::table('m_jenis_produksi')->insert([['id' => 1, 'name' => 'Kayu Gergajian (m3)'], ['id' => 3, 'name' => 'Plywood (m3)']]);
    }

    private function workflow(array $attributes = []): array
    {
        return array_merge([
            'cdk_id' => $this->trenggalek,
            'status' => 'final',
            'approved_by_cdk_at' => '2026-02-03 03:39:55',
            'rejection_note' => 'internal',
            'created_by' => 21,
            'created_at' => '2026-02-02 03:22:31',
            'updated_at' => '2026-02-03 03:39:55',
        ], $attributes);
    }

    private function getAs(?int $cdkId, string $uri, array $abilities = ['bina-usaha.read'])
    {
        $client = IntegrationClient::create(['kode' => 'k-' . uniqid(), 'nama' => 'Klien Uji', 'cdk_id' => $cdkId]);
        $token = $client->createToken('uji', $abilities)->plainTextToken;
        // Setelah create: activity log sempat me-resolve guard dari request sebelumnya.
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->getJson($uri);
    }

    public function test_hasil_hutan_kayu_sums_details_and_picks_pengelola(): void
    {
        $negara = DB::table('hasil_hutan_kayu')->insertGetId($this->workflow([
            'year' => 2025, 'month' => 1, 'province_id' => 35, 'regency_id' => 3503, 'district_id' => 3503130,
            'pengelola_hutan_id' => 5, 'forest_type' => 'Hutan Negara', 'volume_target' => 151.85,
        ]));
        DB::table('hasil_hutan_kayu_details')->insert([
            ['hasil_hutan_kayu_id' => $negara, 'kayu_id' => 1, 'volume_realization' => 200.5],
            ['hasil_hutan_kayu_id' => $negara, 'kayu_id' => 2, 'volume_realization' => 75.03],
        ]);
        DB::table('hasil_hutan_kayu')->insert($this->workflow([
            'year' => 2025, 'month' => 2, 'province_id' => 35, 'regency_id' => 3506,
            'pengelola_wisata_id' => 4, 'forest_type' => 'Perhutanan Sosial', 'updated_at' => '2026-02-04 00:00:00',
        ]));
        DB::table('hasil_hutan_kayu')->insert($this->workflow(['year' => 2025, 'month' => 3, 'province_id' => 35, 'regency_id' => 3503, 'forest_type' => 'Hutan Rakyat', 'status' => 'draft']));

        $data = $this->getAs($this->trenggalek, '/api/v1/bina-usaha/hasil-hutan-kayu')->assertOk()->assertJsonCount(2, 'data')->json('data');

        $this->assertEquals([
            'id' => $negara,
            'tahun' => 2025,
            'bulan' => 1,
            'cdk' => ['id' => $this->trenggalek, 'nama' => 'CDK Wilayah Trenggalek'],
            'wilayah' => [
                'provinsi' => ['id' => '35', 'nama' => 'JAWA TIMUR'],
                'kabupaten' => ['id' => '3503', 'nama' => 'KABUPATEN TRENGGALEK'],
                'kecamatan' => ['id' => '3503130', 'nama' => 'BENDUNGAN'],
                'desa' => null,
            ],
            'jenis_hutan' => 'Hutan Negara',
            'pengelola' => ['id' => 5, 'nama' => 'BKPH Trenggalek'],
            'target_volume_m3' => 151.85,
            'realisasi_volume_m3' => 275.53,
            'rincian' => [
                ['kayu' => ['id' => 1, 'nama' => 'Jati'], 'realisasi_volume_m3' => 200.5],
                ['kayu' => ['id' => 2, 'nama' => 'Mahoni'], 'realisasi_volume_m3' => 75.03],
            ],
            'difinalkan_pada' => '2026-02-03T03:39:55+00:00',
            'dibuat_pada' => '2026-02-02T03:22:31+00:00',
            'diperbarui_pada' => '2026-02-03T03:39:55+00:00',
        ], $data[0]);
        $this->assertSame(['id' => 4, 'nama' => 'Lancar Jaya (Kediri)'], $data[1]['pengelola']);
        $this->assertSame(0, $data[1]['realisasi_volume_m3'] + 0);
        $this->assertSame([], $data[1]['rincian']);
    }

    public function test_hasil_hutan_bukan_kayu_keeps_unit_per_rincian(): void
    {
        $id = DB::table('hasil_hutan_bukan_kayu')->insertGetId($this->workflow([
            'year' => 2025, 'month' => 1, 'province_id' => 35, 'regency_id' => 3503, 'forest_type' => 'Hutan Rakyat', 'volume_target' => 1900.57,
        ]));
        DB::table('hasil_hutan_bukan_kayu_details')->insert([
            ['hasil_hutan_bukan_kayu_id' => $id, 'bukan_kayu_id' => 27, 'annual_volume_realization' => 284, 'unit' => 'batang'],
            // Satuan lama di luar enum Satuan tidak boleh membuat API error.
            ['hasil_hutan_bukan_kayu_id' => $id, 'bukan_kayu_id' => 4, 'annual_volume_realization' => 523, 'unit' => 'Kg'],
        ]);

        $row = $this->getAs(null, '/api/v1/bina-usaha/hasil-hutan-bukan-kayu')->assertOk()->json('data.0');

        $this->assertNull($row['pengelola']);
        $this->assertEquals(1900.57, $row['target_volume']);
        $this->assertArrayNotHasKey('realisasi_volume', $row);
        $this->assertEquals([
            ['komoditas' => ['id' => 27, 'nama' => 'Bambu'], 'realisasi_volume' => 284, 'satuan' => 'batang'],
            ['komoditas' => ['id' => 4, 'nama' => 'Empon - Empon'], 'realisasi_volume' => 523, 'satuan' => 'Kg'],
        ], $row['rincian']);
    }

    public function test_pbphh_has_no_period_and_rejects_period_filters(): void
    {
        $id = DB::table('pbphh')->insertGetId($this->workflow([
            'province_id' => 35, 'regency_id' => 3503, 'district_id' => 3503130,
            'name' => 'UD. ADHI RAJASA MANDIRI', 'number' => '11/14.07/01/I/2022',
            'investment_value' => '550.000.000', 'number_of_workers' => 6, 'present_condition' => 0,
        ]));
        DB::table('pbphh_jenis_produksi')->insert([
            ['pbphh_id' => $id, 'jenis_produksi_id' => 1, 'kapasitas_ijin' => 2000],
            ['pbphh_id' => $id, 'jenis_produksi_id' => 3, 'kapasitas_ijin' => null],
        ]);

        $row = $this->getAs($this->trenggalek, '/api/v1/bina-usaha/pbphh')->assertOk()->json('data.0');

        $this->assertArrayNotHasKey('tahun', $row);
        $this->assertArrayNotHasKey('bulan', $row);
        $this->assertSame('UD. ADHI RAJASA MANDIRI', $row['nama']);
        $this->assertSame('11/14.07/01/I/2022', $row['nomor_izin']);
        $this->assertSame(550000000, $row['nilai_investasi_rp']);
        $this->assertSame(6, $row['jumlah_tenaga_kerja']);
        $this->assertFalse($row['beroperasi']);
        $this->assertEquals([
            ['jenis' => ['id' => 1, 'nama' => 'Kayu Gergajian (m3)'], 'kapasitas_izin' => 2000],
            ['jenis' => ['id' => 3, 'nama' => 'Plywood (m3)'], 'kapasitas_izin' => null],
        ], $row['jenis_produksi']);

        $this->getAs(null, '/api/v1/bina-usaha/pbphh?tahun=2025')->assertStatus(422)->assertJsonValidationErrors('tahun');
        $this->getAs(null, '/api/v1/bina-usaha/pbphh?bulan=1')->assertStatus(422)->assertJsonValidationErrors('bulan');
        $this->getAs(null, '/api/v1/bina-usaha/pbphh?kabupaten_id=3503')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_realisasi_pnbp_parses_rupiah_and_respects_cdk_scope(): void
    {
        $own = DB::table('realisasi_pnbp')->insertGetId($this->workflow([
            'year' => 2025, 'month' => 6, 'province_id' => 35, 'regency_id' => 3506,
            'types_of_forest_products' => 'Non Kayu', 'id_pengelola_wisata' => '4',
            'pnbp_target' => '10725000', 'pnbp_realization' => 'Rp 2.040.000',
        ]));
        DB::table('realisasi_pnbp')->insert($this->workflow([
            'cdk_id' => $this->malang, 'year' => 2025, 'month' => 6, 'province_id' => 35, 'regency_id' => 3506,
            'types_of_forest_products' => 'Kayu', 'id_pengelola_wisata' => '4', 'pnbp_target' => '1', 'pnbp_realization' => '1',
        ]));

        $response = $this->getAs($this->trenggalek, '/api/v1/bina-usaha/realisasi-pnbp')->assertOk()->assertJsonCount(1, 'data');

        $this->assertSame($own, $response->json('data.0.id'));
        $this->assertSame(['id' => 4, 'nama' => 'Lancar Jaya (Kediri)'], $response->json('data.0.pengelola'));
        $this->assertSame(10725000, $response->json('data.0.target_pnbp_rp'));
        $this->assertSame(2040000, $response->json('data.0.realisasi_pnbp_rp'));
        $this->assertNull($response->json('data.0.wilayah.kecamatan'));
        $this->assertArrayNotHasKey('rejection_note', $response->json('data.0'));
    }

    public function test_requires_bina_usaha_ability(): void
    {
        foreach (['hasil-hutan-kayu', 'hasil-hutan-bukan-kayu', 'pbphh', 'realisasi-pnbp'] as $dataset) {
            $this->getAs(null, "/api/v1/bina-usaha/$dataset", ['rhl.read'])->assertStatus(403);
            $this->getAs(null, "/api/v1/bina-usaha/$dataset/deleted?since=2026-01-01", ['rhl.read'])->assertStatus(403);
        }
    }
}
