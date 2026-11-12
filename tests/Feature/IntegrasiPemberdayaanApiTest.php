<?php

namespace Tests\Feature;

use App\Models\IntegrationClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesIsolatedUserDatabase;
use Tests\TestCase;

class IntegrasiPemberdayaanApiTest extends TestCase
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
        foreach (['m_provinces' => null, 'm_regencies' => 'province_id', 'm_districts' => 'regency_id', 'm_villages' => 'district_id'] as $name => $parent) {
            Schema::create($name, function (Blueprint $table) use ($parent) {
                $table->string('id')->primary();
                if ($parent) {
                    $table->string($parent);
                }
                $table->string('name');
            });
        }
        Schema::create('m_skema_perhutanan_sosial', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('m_commodities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_nilai_transaksi_ekonomi')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
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
        $wilayah = function (Blueprint $t, bool $village = false) {
            $t->unsignedBigInteger('province_id')->nullable();
            $t->unsignedBigInteger('regency_id')->nullable();
            $t->unsignedBigInteger('district_id')->nullable();
            if ($village) {
                $t->unsignedBigInteger('village_id')->nullable();
            }
        };
        Schema::create('skps', function (Blueprint $t) use ($workflow, $wilayah) {
            $t->id();
            $wilayah($t);
            $t->string('id_skema_perhutanan_sosial');
            $t->string('nama_kelompok')->nullable();
            $t->text('potential');
            $t->decimal('ps_area', 15, 2)->default(0);
            $t->integer('number_of_kk')->default(0);
            $workflow($t);
        });
        Schema::create('kups', function (Blueprint $t) use ($workflow, $wilayah) {
            $t->id();
            $wilayah($t);
            $t->string('nama_kups')->nullable();
            $t->string('category');
            $t->string('commodity');
            $workflow($t);
        });
        Schema::create('perkembangan_kth', function (Blueprint $t) use ($workflow, $wilayah) {
            $t->id();
            $t->integer('year');
            $t->tinyInteger('month');
            $wilayah($t, true);
            $t->string('nama_kth');
            $t->string('nomor_register')->nullable();
            $t->string('kelas_kelembagaan');
            $t->integer('jumlah_anggota')->default(0);
            $t->decimal('luas_kelola', 15, 2)->default(0);
            $t->text('potensi_kawasan')->nullable();
            $workflow($t);
        });
        Schema::create('nilai_ekonomi', function (Blueprint $t) use ($workflow, $wilayah) {
            $t->id();
            $t->string('nama_kelompok');
            $t->decimal('total_transaction_value', 19, 2)->default(0);
            $t->integer('year');
            $t->integer('month');
            $wilayah($t);
            $workflow($t);
        });
        Schema::create('nilai_ekonomi_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('nilai_ekonomi_id');
            $t->unsignedBigInteger('commodity_id');
            $t->decimal('production_volume', 15, 2);
            $t->string('satuan')->default('Kg');
            $t->decimal('transaction_value', 19, 2);
            $t->timestamps();
        });
        Schema::create('nilai_transaksi_ekonomi', function (Blueprint $t) use ($workflow, $wilayah) {
            $t->id();
            $t->integer('year');
            $t->integer('month');
            $wilayah($t, true);
            $t->string('nama_kth');
            $t->decimal('total_nilai_transaksi', 20, 2)->default(0);
            $workflow($t);
        });
        Schema::create('nilai_transaksi_ekonomi_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('nilai_transaksi_ekonomi_id');
            $t->unsignedBigInteger('commodity_id');
            $t->decimal('volume_produksi', 15, 2)->default(0);
            $t->string('satuan')->default('Kg');
            $t->decimal('nilai_transaksi', 20, 2)->default(0);
            $t->timestamps();
        });
        (require base_path('vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require base_path('database/migrations/2026_11_01_000001_create_integration_clients_table.php'))->up();

        $this->trenggalek = DB::table('cdks')->insertGetId(['kode' => 'CDK Wilayah Trenggalek', 'nama' => 'CDK Wilayah Trenggalek']);
        $this->malang = DB::table('cdks')->insertGetId(['kode' => 'CDK Wilayah Malang', 'nama' => 'CDK Wilayah Malang']);
        DB::table('m_provinces')->insert(['id' => '35', 'name' => 'JAWA TIMUR']);
        DB::table('m_regencies')->insert(['id' => '3504', 'province_id' => '35', 'name' => 'KABUPATEN TULUNGAGUNG']);
        DB::table('m_districts')->insert(['id' => '3504010', 'regency_id' => '3504', 'name' => 'BESUKI']);
        DB::table('m_villages')->insert(['id' => '3504010009', 'district_id' => '3504010', 'name' => 'SIYOTOBAGUS']);
        DB::table('m_skema_perhutanan_sosial')->insert(['id' => 2, 'name' => 'Hutan Kemasyarakatan (HKm)']);
        DB::table('m_commodities')->insert([
            ['id' => 12, 'name' => 'Pisang', 'is_nilai_transaksi_ekonomi' => false, 'deleted_at' => null],
            ['id' => 23, 'name' => 'Log', 'is_nilai_transaksi_ekonomi' => true, 'deleted_at' => null],
            ['id' => 30, 'name' => 'Komoditas Lama', 'is_nilai_transaksi_ekonomi' => false, 'deleted_at' => '2026-01-01 00:00:00'],
        ]);
    }

    private function workflow(array $attributes = []): array
    {
        return array_merge([
            'cdk_id' => $this->trenggalek,
            'province_id' => 35,
            'regency_id' => 3504,
            'district_id' => 3504010,
            'status' => 'final',
            'approved_by_cdk_at' => '2026-01-27 11:43:25',
            'rejection_note' => 'internal',
            'created_by' => 20,
            'created_at' => '2026-01-26 08:01:49',
            'updated_at' => '2026-01-27 11:43:25',
        ], $attributes);
    }

    private function getAs(?int $cdkId, string $uri, array $abilities = ['pemberdayaan.read'])
    {
        $client = IntegrationClient::create(['kode' => 'k-' . uniqid(), 'nama' => 'Klien Uji', 'cdk_id' => $cdkId]);
        $token = $client->createToken('uji', $abilities)->plainTextToken;
        // Setelah create: activity log sempat me-resolve guard dari request sebelumnya.
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->getJson($uri);
    }

    public function test_skps_and_kups_have_no_period(): void
    {
        DB::table('skps')->insert($this->workflow([
            'id_skema_perhutanan_sosial' => '2', 'nama_kelompok' => 'KTH Wono Rukun Sejati',
            'potential' => 'Sengon, Kopi', 'ps_area' => 223, 'number_of_kk' => 301,
        ]));
        DB::table('kups')->insert($this->workflow(['nama_kups' => 'KUPS Raja Pisang AML', 'category' => 'Blue', 'commodity' => 'Pisang']));

        $skps = $this->getAs($this->trenggalek, '/api/v1/pemberdayaan/skps')->assertOk()->json('data.0');
        $this->assertArrayNotHasKey('tahun', $skps);
        $this->assertSame(['id' => 2, 'nama' => 'Hutan Kemasyarakatan (HKm)'], $skps['skema']);
        $this->assertEquals(223, $skps['luas_ps_ha']);
        $this->assertSame(301, $skps['jumlah_kk']);
        $this->assertSame('Sengon, Kopi', $skps['potensi']);
        $this->assertSame('BESUKI', $skps['wilayah']['kecamatan']['nama']);

        $kups = $this->getAs($this->trenggalek, '/api/v1/pemberdayaan/kups')->assertOk()->json('data.0');
        $this->assertArrayNotHasKey('bulan', $kups);
        $this->assertSame(['nama_kups' => 'KUPS Raja Pisang AML', 'kelas' => 'Blue', 'komoditas' => 'Pisang'], array_intersect_key($kups, array_flip(['nama_kups', 'kelas', 'komoditas'])));

        $this->getAs(null, '/api/v1/pemberdayaan/skps?tahun=2025')->assertStatus(422)->assertJsonValidationErrors('tahun');
        $this->getAs(null, '/api/v1/pemberdayaan/kups?bulan=1')->assertStatus(422)->assertJsonValidationErrors('bulan');
    }

    public function test_perkembangan_kth_shape_with_village(): void
    {
        $id = DB::table('perkembangan_kth')->insertGetId($this->workflow([
            'year' => 2025, 'month' => 11, 'village_id' => 3504010009,
            'nama_kth' => 'Karya Taruna', 'nomor_register' => '35/04/15/2009/KTH.159/2003',
            'kelas_kelembagaan' => 'madya', 'jumlah_anggota' => 26, 'luas_kelola' => 12.5, 'potensi_kawasan' => 'Hutan Rakyat',
        ]));

        $this->assertEquals([
            'id' => $id,
            'tahun' => 2025,
            'bulan' => 11,
            'cdk' => ['id' => $this->trenggalek, 'nama' => 'CDK Wilayah Trenggalek'],
            'wilayah' => [
                'provinsi' => ['id' => '35', 'nama' => 'JAWA TIMUR'],
                'kabupaten' => ['id' => '3504', 'nama' => 'KABUPATEN TULUNGAGUNG'],
                'kecamatan' => ['id' => '3504010', 'nama' => 'BESUKI'],
                'desa' => ['id' => '3504010009', 'nama' => 'SIYOTOBAGUS'],
            ],
            'nama_kth' => 'Karya Taruna',
            'nomor_register' => '35/04/15/2009/KTH.159/2003',
            'kelas_kelembagaan' => 'madya',
            'jumlah_anggota' => 26,
            'luas_kelola_ha' => 12.5,
            'potensi_kawasan' => 'Hutan Rakyat',
            'difinalkan_pada' => '2026-01-27T11:43:25+00:00',
            'dibuat_pada' => '2026-01-26T08:01:49+00:00',
            'diperbarui_pada' => '2026-01-27T11:43:25+00:00',
        ], $this->getAs(null, '/api/v1/pemberdayaan/perkembangan-kth')->assertOk()->json('data.0'));
    }

    public function test_nilai_transaksi_ekonomi_includes_scoped_and_deleted_commodities(): void
    {
        $id = DB::table('nilai_transaksi_ekonomi')->insertGetId($this->workflow([
            'year' => 2025, 'month' => 8, 'village_id' => 3504010009, 'nama_kth' => 'Tani Makmur', 'total_nilai_transaksi' => 5350000,
        ]));
        DB::table('nilai_transaksi_ekonomi_details')->insert([
            // Komoditas khusus transaksi: disembunyikan global scope Commodity di aplikasi web.
            ['nilai_transaksi_ekonomi_id' => $id, 'commodity_id' => 23, 'volume_produksi' => 5, 'satuan' => 'm3', 'nilai_transaksi' => 3450000],
            ['nilai_transaksi_ekonomi_id' => $id, 'commodity_id' => 30, 'volume_produksi' => 2, 'satuan' => 'kg', 'nilai_transaksi' => 1900000],
        ]);
        DB::table('nilai_transaksi_ekonomi')->insert($this->workflow(['cdk_id' => $this->malang, 'year' => 2025, 'month' => 8, 'nama_kth' => 'Lain']));

        $response = $this->getAs($this->trenggalek, '/api/v1/pemberdayaan/nilai-transaksi-ekonomi')->assertOk()->assertJsonCount(1, 'data');

        $this->assertEquals(5350000, $response->json('data.0.total_nilai_transaksi_rp'));
        $this->assertSame('SIYOTOBAGUS', $response->json('data.0.wilayah.desa.nama'));
        $this->assertEquals([
            ['komoditas' => ['id' => 23, 'nama' => 'Log'], 'volume_produksi' => 5, 'satuan' => 'm3', 'nilai_transaksi_rp' => 3450000],
            ['komoditas' => ['id' => 30, 'nama' => 'Komoditas Lama'], 'volume_produksi' => 2, 'satuan' => 'kg', 'nilai_transaksi_rp' => 1900000],
        ], $response->json('data.0.rincian'));
    }

    public function test_nilai_ekonomi_shape(): void
    {
        $id = DB::table('nilai_ekonomi')->insertGetId($this->workflow([
            'year' => 2025, 'month' => 12, 'nama_kelompok' => 'KTH Argo Makmur Lestari', 'total_transaction_value' => 134800000,
        ]));
        DB::table('nilai_ekonomi_details')->insert([
            'nilai_ekonomi_id' => $id, 'commodity_id' => 12, 'production_volume' => 674, 'satuan' => 'ton', 'transaction_value' => 134800000,
        ]);

        $row = $this->getAs(null, '/api/v1/pemberdayaan/nilai-ekonomi')->assertOk()->json('data.0');

        $this->assertSame('KTH Argo Makmur Lestari', $row['nama_kelompok']);
        $this->assertEquals(134800000, $row['total_nilai_transaksi_rp']);
        $this->assertEquals([['komoditas' => ['id' => 12, 'nama' => 'Pisang'], 'volume_produksi' => 674, 'satuan' => 'ton', 'nilai_transaksi_rp' => 134800000]], $row['rincian']);
        $this->assertNull($row['wilayah']['desa']);
        $this->assertArrayNotHasKey('rejection_note', $row);
    }

    public function test_requires_pemberdayaan_ability(): void
    {
        foreach (['skps', 'kups', 'perkembangan-kth', 'nilai-ekonomi', 'nilai-transaksi-ekonomi'] as $dataset) {
            $this->getAs(null, "/api/v1/pemberdayaan/$dataset", ['rhl.read'])->assertStatus(403);
            $this->getAs(null, "/api/v1/pemberdayaan/$dataset/deleted?since=2026-01-01", ['rhl.read'])->assertStatus(403);
        }
    }

    public function test_rekap_counts_each_kth_once_and_sums_per_kabupaten(): void
    {
        DB::table('m_regencies')->insert(['id' => '3503', 'province_id' => '35', 'name' => 'KABUPATEN TRENGGALEK']);
        $kth = fn (array $a) => DB::table('perkembangan_kth')->insert($this->workflow(array_merge([
            'year' => 2015, 'month' => 1, 'nama_kth' => 'KTH', 'kelas_kelembagaan' => 'pemula', 'regency_id' => 3503,
        ], $a)));
        // Satu KTH, tiga catatan: yang dipakai catatan terbaru (2023, 30 anggota).
        $kth(['nomor_register' => '35/03/KTH.013/2016', 'year' => 2016, 'jumlah_anggota' => 74, 'luas_kelola' => 1]);
        $kth(['nomor_register' => '35/03/KTH.013/2016', 'year' => 2023, 'jumlah_anggota' => 30, 'luas_kelola' => 10.5]);
        $kth(['nomor_register' => ' 35/03/kth.013/2016 ', 'year' => 2016, 'month' => 2, 'jumlah_anggota' => 30]);
        $kth(['nomor_register' => '35/04/KTH.159/2003', 'regency_id' => 3504, 'jumlah_anggota' => 26]);
        // Tanpa nomor register: masing-masing dihitung sendiri.
        $kth(['nomor_register' => null, 'regency_id' => 3504, 'jumlah_anggota' => 5]);
        $kth(['nomor_register' => '', 'regency_id' => 3504, 'jumlah_anggota' => 7]);
        // Tidak ikut: belum final, CDK lain, dan sudah dihapus.
        $kth(['nomor_register' => 'DRAFT', 'status' => 'waiting_cdk', 'jumlah_anggota' => 99]);
        $kth(['nomor_register' => 'MALANG', 'cdk_id' => $this->malang, 'jumlah_anggota' => 99]);
        $kth(['nomor_register' => 'HAPUS', 'deleted_at' => '2026-02-01 00:00:00', 'jumlah_anggota' => 99]);

        $skps = fn (array $a) => DB::table('skps')->insert($this->workflow(array_merge(['id_skema_perhutanan_sosial' => '2', 'potential' => '-'], $a)));
        $skps(['regency_id' => 3503, 'ps_area' => 100.5, 'number_of_kk' => 200]);
        $skps(['regency_id' => 3504, 'ps_area' => 50, 'number_of_kk' => 101]);
        foreach (['Blue', 'Gold', 'Blue'] as $kelas) {
            DB::table('kups')->insert($this->workflow(['category' => $kelas, 'commodity' => 'Pisang']));
        }

        $transaksi = fn (array $a) => DB::table('nilai_transaksi_ekonomi')->insert($this->workflow(array_merge(['nama_kth' => 'KTH', 'regency_id' => 3503], $a)));
        $transaksi(['year' => 2025, 'month' => 12, 'total_nilai_transaksi' => 900]);
        $transaksi(['year' => 2026, 'month' => 1, 'total_nilai_transaksi' => 1000.5]);
        $transaksi(['year' => 2026, 'month' => 1, 'total_nilai_transaksi' => 500, 'regency_id' => 3504]);
        $transaksi(['year' => 2026, 'month' => 9, 'total_nilai_transaksi' => 250, 'updated_at' => '2026-10-23 03:00:00']);
        $transaksi(['year' => 2026, 'month' => 10, 'total_nilai_transaksi' => 7777, 'status' => 'draft']);
        DB::table('nilai_ekonomi')->insert($this->workflow(['year' => 2026, 'month' => 3, 'nama_kelompok' => 'Gapoktan', 'total_transaction_value' => 3700]));

        $data = $this->getAs($this->trenggalek, '/api/v1/pemberdayaan/rekap')->assertOk()->assertJsonStructure(['data', 'waktu_server'])->json('data');

        // Tahun bawaan = tahun terakhir yang punya data final.
        $this->assertSame(2026, $data['tahun']);
        $this->assertSame([2025, 2026], $data['tahun_tersedia']);
        $this->assertEquals(['jumlah' => 4, 'jumlah_anggota' => 68, 'luas_kelola_ha' => 10.5, 'jumlah_catatan' => 6], $data['kth']);
        $this->assertEquals(['jumlah_kelompok' => 2, 'jumlah_kk' => 301, 'luas_ps_ha' => 150.5], $data['perhutanan_sosial']);
        $this->assertEquals(['jumlah' => 3, 'per_kelas' => [['kelas' => 'Blue', 'jumlah' => 2], ['kelas' => 'Gold', 'jumlah' => 1]]], $data['kups']);
        $this->assertEquals([
            'total_rp' => 1750.5,
            'jumlah_catatan' => 3,
            'periode' => ['bulan_awal' => 1, 'bulan_akhir' => 9],
            'per_bulan' => [
                ['bulan' => 1, 'jumlah_catatan' => 2, 'total_rp' => 1500.5],
                ['bulan' => 9, 'jumlah_catatan' => 1, 'total_rp' => 250],
            ],
        ], $data['nilai_transaksi_ekonomi']);
        $this->assertEquals(3700, $data['nilai_ekonomi']['total_rp']);
        $this->assertEquals([
            [
                'kabupaten' => ['id' => '3503', 'nama' => 'KABUPATEN TRENGGALEK'],
                'kth' => ['jumlah' => 1, 'jumlah_anggota' => 30, 'luas_kelola_ha' => 10.5],
                'perhutanan_sosial' => ['jumlah_kelompok' => 1, 'jumlah_kk' => 200, 'luas_ps_ha' => 100.5],
                'nilai_transaksi_ekonomi_rp' => 1250.5,
            ],
            [
                'kabupaten' => ['id' => '3504', 'nama' => 'KABUPATEN TULUNGAGUNG'],
                'kth' => ['jumlah' => 3, 'jumlah_anggota' => 38, 'luas_kelola_ha' => 0],
                'perhutanan_sosial' => ['jumlah_kelompok' => 1, 'jumlah_kk' => 101, 'luas_ps_ha' => 50],
                'nilai_transaksi_ekonomi_rp' => 500,
            ],
        ], $data['per_kabupaten']);
        $this->assertSame('2026-10-23T03:00:00+00:00', $data['diperbarui_pada']);

        $filtered = $this->getAs($this->trenggalek, '/api/v1/pemberdayaan/rekap?tahun=2025&kabupaten_id=3503')->assertOk()->json('data');
        $this->assertSame(2025, $filtered['tahun']);
        $this->assertEquals(900, $filtered['nilai_transaksi_ekonomi']['total_rp']);
        $this->assertEquals(['jumlah' => 1, 'jumlah_anggota' => 30, 'luas_kelola_ha' => 10.5, 'jumlah_catatan' => 3], $filtered['kth']);
        $this->assertCount(1, $filtered['per_kabupaten']);

        // Klien provinsi ikut melihat CDK lain.
        $this->assertSame(5, $this->getAs(null, '/api/v1/pemberdayaan/rekap')->json('data.kth.jumlah'));
    }

    public function test_rekap_without_data_and_validation(): void
    {
        $data = $this->getAs(null, '/api/v1/pemberdayaan/rekap')->assertOk()->json('data');

        $this->assertNull($data['tahun']);
        $this->assertSame([], $data['tahun_tersedia']);
        $this->assertSame(0, $data['kth']['jumlah']);
        $this->assertNull($data['nilai_transaksi_ekonomi']['periode']);
        $this->assertSame([], $data['per_kabupaten']);
        $this->assertNull($data['diperbarui_pada']);

        $this->getAs(null, '/api/v1/pemberdayaan/rekap?tahun=abc')->assertStatus(422)->assertJsonValidationErrors('tahun');
        $this->getAs(null, '/api/v1/pemberdayaan/rekap', ['rhl.read'])->assertStatus(403);
    }
}
