<?php

namespace Tests\Feature;

use App\Models\IntegrationClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesIsolatedUserDatabase;
use Tests\TestCase;

class IntegrasiPerlindunganApiTest extends TestCase
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
        Schema::create('m_pengelola_wisata', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
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
        Schema::create('kebakaran_hutan', function (Blueprint $t) use ($workflow) {
            $t->id();
            $t->integer('year');
            $t->tinyInteger('month');
            $t->string('province_id')->nullable();
            $t->string('regency_id')->nullable();
            $t->string('district_id')->nullable();
            $t->string('village_id')->nullable();
            $t->string('coordinates')->nullable();
            $t->string('id_pengelola_wisata');
            $t->string('area_function');
            $t->integer('number_of_fires');
            $t->string('fire_area');
            $workflow($t);
        });
        Schema::create('pengunjung_wisata', function (Blueprint $t) use ($workflow) {
            $t->id();
            $t->integer('year');
            $t->tinyInteger('month');
            $t->string('id_pengelola_wisata');
            $t->string('number_of_visitors');
            $t->string('gross_income');
            $workflow($t);
        });
        (require base_path('vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require base_path('database/migrations/2026_11_01_000001_create_integration_clients_table.php'))->up();

        $this->trenggalek = DB::table('cdks')->insertGetId(['kode' => 'CDK Wilayah Trenggalek', 'nama' => 'CDK Wilayah Trenggalek']);
        $this->malang = DB::table('cdks')->insertGetId(['kode' => 'CDK Wilayah Malang', 'nama' => 'CDK Wilayah Malang']);
        DB::table('m_provinces')->insert(['id' => '35', 'name' => 'JAWA TIMUR']);
        DB::table('m_regencies')->insert([
            ['id' => '3503', 'province_id' => '35', 'name' => 'KABUPATEN TRENGGALEK'],
            ['id' => '3504', 'province_id' => '35', 'name' => 'KABUPATEN TULUNGAGUNG'],
        ]);
        DB::table('m_districts')->insert(['id' => '3503110', 'regency_id' => '3503', 'name' => 'TRENGGALEK']);
        DB::table('m_villages')->insert(['id' => '3503110005', 'district_id' => '3503110', 'name' => 'NGANTRU']);
        DB::table('m_pengelola_wisata')->insert([
            ['id' => 2, 'name' => 'KPH Kediri'],
            ['id' => 11, 'name' => 'Tani Sentosa (Trenggalek)'],
        ]);
    }

    private function fire(array $attributes = []): int
    {
        return DB::table('kebakaran_hutan')->insertGetId(array_merge([
            'cdk_id' => $this->trenggalek,
            'year' => 2026,
            'month' => 8,
            'province_id' => '35',
            'regency_id' => '3503',
            'district_id' => '3503110',
            'village_id' => '3503110005',
            'id_pengelola_wisata' => '2',
            'area_function' => 'Hutan Produksi (HP)',
            'number_of_fires' => 2,
            'fire_area' => '1,5',
            'status' => 'final',
            'approved_by_cdk_at' => '2026-08-30 04:11:42',
            'rejection_note' => 'internal',
            'created_at' => '2026-08-20 03:12:12',
            'updated_at' => '2026-08-30 04:11:42',
        ], $attributes));
    }

    private function visit(array $attributes = []): int
    {
        return DB::table('pengunjung_wisata')->insertGetId(array_merge([
            'cdk_id' => $this->trenggalek,
            'year' => 2026,
            'month' => 1,
            'id_pengelola_wisata' => '11',
            'number_of_visitors' => '1438',
            'gross_income' => '14380000',
            'status' => 'final',
            'approved_by_cdk_at' => '2026-02-18 03:41:07',
            'created_at' => '2026-02-10 08:54:25',
            'updated_at' => '2026-02-18 03:41:07',
        ], $attributes));
    }

    private function getAs(?int $cdkId, string $uri, array $abilities = ['perlindungan.read'])
    {
        $client = IntegrationClient::create(['kode' => 'k-' . uniqid(), 'nama' => 'Klien Uji', 'cdk_id' => $cdkId]);
        $token = $client->createToken('uji', $abilities)->plainTextToken;
        // Setelah create: activity log sempat me-resolve guard dari request sebelumnya.
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->getJson($uri);
    }

    public function test_kebakaran_hutan_shape_and_comma_decimal_area(): void
    {
        $id = $this->fire();
        $this->fire(['status' => 'waiting_cdk']);

        $row = $this->getAs($this->trenggalek, '/api/v1/perlindungan/kebakaran-hutan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->json('data.0');

        $this->assertEquals([
            'id' => $id,
            'tahun' => 2026,
            'bulan' => 8,
            'cdk' => ['id' => $this->trenggalek, 'nama' => 'CDK Wilayah Trenggalek'],
            'wilayah' => [
                'provinsi' => ['id' => '35', 'nama' => 'JAWA TIMUR'],
                'kabupaten' => ['id' => '3503', 'nama' => 'KABUPATEN TRENGGALEK'],
                'kecamatan' => ['id' => '3503110', 'nama' => 'TRENGGALEK'],
                'desa' => ['id' => '3503110005', 'nama' => 'NGANTRU'],
            ],
            'pengelola' => ['id' => 2, 'nama' => 'KPH Kediri'],
            'fungsi_kawasan' => 'Hutan Produksi (HP)',
            'jumlah_kejadian' => 2,
            'luas_terbakar_ha' => 1.5,
            'koordinat' => null,
            'difinalkan_pada' => '2026-08-30T04:11:42+00:00',
            'dibuat_pada' => '2026-08-20T03:12:12+00:00',
            'diperbarui_pada' => '2026-08-30T04:11:42+00:00',
        ], $row);
    }

    public function test_fire_area_text_formats_are_parsed(): void
    {
        $cases = ['0.5' => 0.5, '2' => 2, '1.250,75' => 1250.75, '1.250.000' => 1250000, ' 3,25 ha' => 3.25, 'tidak diketahui' => null, '' => null];
        foreach ($cases as $raw => $expected) {
            $this->fire(['fire_area' => (string) $raw, 'updated_at' => '2026-08-30 04:11:' . str_pad((string) count($cases), 2, '0')]);
        }

        $areas = $this->getAs(null, '/api/v1/perlindungan/kebakaran-hutan')->assertOk()->json('data.*.luas_terbakar_ha');

        $this->assertEquals(array_values($cases), $areas);
    }

    public function test_pengunjung_wisata_has_no_wilayah_and_rejects_kabupaten_filter(): void
    {
        $id = $this->visit();
        $this->visit(['number_of_visitors' => '2.120', 'gross_income' => 'Rp 21.200.000', 'updated_at' => '2026-02-19 00:00:00']);
        $this->visit(['cdk_id' => $this->malang]);

        $response = $this->getAs($this->trenggalek, '/api/v1/perlindungan/pengunjung-wisata')->assertOk()->assertJsonCount(2, 'data');

        $this->assertEquals([
            'id' => $id,
            'tahun' => 2026,
            'bulan' => 1,
            'cdk' => ['id' => $this->trenggalek, 'nama' => 'CDK Wilayah Trenggalek'],
            'pengelola' => ['id' => 11, 'nama' => 'Tani Sentosa (Trenggalek)'],
            'jumlah_pengunjung' => 1438,
            'pendapatan_bruto_rp' => 14380000,
            'difinalkan_pada' => '2026-02-18T03:41:07+00:00',
            'dibuat_pada' => '2026-02-10T08:54:25+00:00',
            'diperbarui_pada' => '2026-02-18T03:41:07+00:00',
        ], $response->json('data.0'));
        $this->assertSame(2120, $response->json('data.1.jumlah_pengunjung'));
        $this->assertSame(21200000, $response->json('data.1.pendapatan_bruto_rp'));

        $this->getAs(null, '/api/v1/perlindungan/pengunjung-wisata?kabupaten_id=3503')
            ->assertStatus(422)
            ->assertJsonValidationErrors('kabupaten_id');
    }

    public function test_kabupaten_filter_cdk_scope_and_deleted_endpoint(): void
    {
        $own = $this->fire();
        $this->fire(['regency_id' => '3504']);
        $this->fire(['cdk_id' => $this->malang]);
        $deleted = $this->fire(['deleted_at' => '2026-09-02 00:00:00']);

        $this->assertSame([$own], $this->getAs($this->trenggalek, '/api/v1/perlindungan/kebakaran-hutan?kabupaten_id=3503')->json('data.*.id'));
        $this->assertCount(3, $this->getAs(null, '/api/v1/perlindungan/kebakaran-hutan')->json('data'));

        $this->assertSame(
            [['id' => $deleted, 'alasan' => 'dihapus', 'waktu' => '2026-09-02T00:00:00+00:00']],
            $this->getAs($this->trenggalek, '/api/v1/perlindungan/kebakaran-hutan/deleted?since=2026-09-01T00:00:00Z')->assertOk()->json('data')
        );
    }

    public function test_requires_perlindungan_ability(): void
    {
        foreach (['kebakaran-hutan', 'pengunjung-wisata'] as $dataset) {
            $this->getAs(null, "/api/v1/perlindungan/$dataset", ['rhl.read'])->assertStatus(403);
            $this->getAs(null, "/api/v1/perlindungan/$dataset/deleted?since=2026-01-01", ['rhl.read'])->assertStatus(403);
        }
    }

    public function test_rekap_sums_text_columns_and_lists_reporting_pengelola(): void
    {
        $this->fire(['month' => 6]);
        $this->fire(['month' => 9, 'regency_id' => '3504', 'number_of_fires' => 1, 'fire_area' => '22.6', 'updated_at' => '2026-10-01 00:00:00']);
        $this->fire(['fire_area' => 'tidak diketahui', 'number_of_fires' => 3]);
        $this->fire(['year' => 2025]);
        $this->fire(['status' => 'waiting_cdk']);
        $this->fire(['cdk_id' => $this->malang]);

        $this->visit();
        $this->visit(['month' => 8, 'number_of_visitors' => '2.120', 'gross_income' => 'Rp 21.200.000']);
        $this->visit(['month' => 3, 'id_pengelola_wisata' => '2', 'number_of_visitors' => '10', 'gross_income' => '100000']);
        $this->visit(['year' => 2025, 'number_of_visitors' => '999']);
        $this->visit(['cdk_id' => $this->malang]);

        $data = $this->getAs($this->trenggalek, '/api/v1/perlindungan/rekap')->assertOk()->json('data');

        $this->assertSame(2026, $data['tahun']);
        $this->assertSame([2025, 2026], $data['tahun_tersedia']);
        $this->assertEquals([
            'jumlah_kejadian' => 6,
            'luas_terbakar_ha' => 24.1,
            'periode' => ['bulan_awal' => 6, 'bulan_akhir' => 9],
            'per_kabupaten' => [
                ['kabupaten' => ['id' => '3503', 'nama' => 'KABUPATEN TRENGGALEK'], 'jumlah_kejadian' => 5, 'luas_terbakar_ha' => 1.5],
                ['kabupaten' => ['id' => '3504', 'nama' => 'KABUPATEN TULUNGAGUNG'], 'jumlah_kejadian' => 1, 'luas_terbakar_ha' => 22.6],
            ],
        ], $data['kebakaran_hutan']);
        $this->assertEquals([
            'jumlah_pengunjung' => 3568,
            'pendapatan_bruto_rp' => 35680000,
            'periode' => ['bulan_awal' => 1, 'bulan_akhir' => 8],
            'per_pengelola' => [
                ['pengelola' => ['id' => 11, 'nama' => 'Tani Sentosa (Trenggalek)'], 'jumlah_pengunjung' => 3558, 'pendapatan_bruto_rp' => 35580000, 'periode' => ['bulan_awal' => 1, 'bulan_akhir' => 8]],
                ['pengelola' => ['id' => 2, 'nama' => 'KPH Kediri'], 'jumlah_pengunjung' => 10, 'pendapatan_bruto_rp' => 100000, 'periode' => ['bulan_awal' => 3, 'bulan_akhir' => 3]],
            ],
        ], $data['pengunjung_wisata']);
        $this->assertSame('2026-10-01T00:00:00+00:00', $data['diperbarui_pada']);

        // Pengunjung wisata tidak punya lokasi: null bila difilter per kabupaten.
        $filtered = $this->getAs($this->trenggalek, '/api/v1/perlindungan/rekap?tahun=2026&kabupaten_id=3504')->assertOk()->json('data');
        $this->assertSame(1, $filtered['kebakaran_hutan']['jumlah_kejadian']);
        $this->assertNull($filtered['pengunjung_wisata']);

        $this->assertSame(999, $this->getAs($this->trenggalek, '/api/v1/perlindungan/rekap?tahun=2025')->json('data.pengunjung_wisata.jumlah_pengunjung'));
        $this->getAs(null, '/api/v1/perlindungan/rekap', ['rhl.read'])->assertStatus(403);
    }
}
