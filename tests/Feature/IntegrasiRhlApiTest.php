<?php

namespace Tests\Feature;

use App\Models\IntegrationClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesIsolatedUserDatabase;
use Tests\TestCase;

class IntegrasiRhlApiTest extends TestCase
{
    use UsesIsolatedUserDatabase { setUp as setUpUserDatabase; }

    private const DATASETS = [
        'rehab-lahan' => 'rehab_lahan',
        'penghijauan-lingkungan' => 'penghijauan_lingkungan',
        'rehab-mangrove' => 'rehab_manggrove',
        'reboisasi-ps' => 'reboisasi_ps',
        'rhl-teknis' => 'rhl_teknis',
    ];

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
        foreach (['m_pengelola_ps', 'm_bangunan_kta'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
        foreach (self::DATASETS as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->unsignedBigInteger('cdk_id')->nullable();
                $t->integer('year');
                $t->tinyInteger('month');
                $t->string('province_id')->nullable();
                $t->string('regency_id')->nullable();
                $t->string('district_id')->nullable();
                $t->string('village_id')->nullable();
                $t->decimal('target_annual', 15, 2)->default(0);
                if ($table !== 'rhl_teknis') {
                    $t->decimal('realization', 15, 2)->default(0);
                    $t->string('coordinates')->nullable();
                }
                if ($table === 'reboisasi_ps') {
                    $t->unsignedBigInteger('pengelola_id')->nullable();
                }
                $t->string('fund_source');
                $t->string('status')->default('draft');
                $t->timestamp('approved_by_kasi_at')->nullable();
                $t->timestamp('approved_by_cdk_at')->nullable();
                $t->text('rejection_note')->nullable();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->unsignedBigInteger('updated_by')->nullable();
                $t->unsignedBigInteger('deleted_by')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        }
        Schema::create('rhl_teknis_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rhl_teknis_id');
            $table->unsignedBigInteger('bangunan_kta_id')->nullable();
            $table->integer('unit_amount')->default(0);
            $table->timestamps();
        });
        (require base_path('vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require base_path('database/migrations/2026_11_01_000001_create_integration_clients_table.php'))->up();

        $this->trenggalek = DB::table('cdks')->insertGetId(['kode' => 'CDK Wilayah Trenggalek', 'nama' => 'CDK Wilayah Trenggalek']);
        $this->malang = DB::table('cdks')->insertGetId(['kode' => 'CDK Wilayah Malang', 'nama' => 'CDK Wilayah Malang']);
        DB::table('m_provinces')->insert(['id' => '35', 'name' => 'JAWA TIMUR']);
        DB::table('m_regencies')->insert([
            ['id' => '3503', 'province_id' => '35', 'name' => 'KABUPATEN TRENGGALEK'],
            ['id' => '3507', 'province_id' => '35', 'name' => 'KABUPATEN MALANG'],
        ]);
        DB::table('m_districts')->insert(['id' => '3503060', 'regency_id' => '3503', 'name' => 'PULE']);
        DB::table('m_villages')->insert(['id' => '3503060009', 'district_id' => '3503060', 'name' => 'KARANGANYAR']);
        DB::table('m_pengelola_ps')->insert(['id' => 1, 'name' => 'KTH Lancar Jaya']);
        DB::table('m_bangunan_kta')->insert(['id' => 1, 'name' => 'Dam Penahan']);
    }

    private function row(string $table, array $attributes = []): int
    {
        $defaults = [
            'cdk_id' => $this->trenggalek,
            'year' => 2026,
            'month' => 3,
            'province_id' => '35',
            'regency_id' => '3503',
            'district_id' => '3503060',
            'village_id' => '3503060009',
            'target_annual' => 5,
            'fund_source' => 'APBN',
            'status' => 'final',
            'approved_by_cdk_at' => '2026-03-27 11:05:06',
            'rejection_note' => 'catatan internal',
            'created_by' => 7,
            'created_at' => '2026-03-27 02:00:00',
            'updated_at' => '2026-03-27 11:05:06',
        ];
        if ($table !== 'rhl_teknis') {
            $defaults['realization'] = 4.5;
            $defaults['coordinates'] = '-8.1,111.7';
        }

        return DB::table($table)->insertGetId(array_merge($defaults, $attributes));
    }

    private function token(?int $cdkId, array $abilities = ['rhl.read']): string
    {
        $client = IntegrationClient::create([
            'kode' => 'klien-' . ($cdkId ?? 'provinsi') . '-' . uniqid(),
            'nama' => 'Klien Uji',
            'cdk_id' => $cdkId,
        ]);

        return $client->createToken('uji', $abilities)->plainTextToken;
    }

    private function getAs(string $token, string $uri)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->getJson($uri);
    }

    public function test_rehab_lahan_returns_final_rows_in_stable_documented_shape(): void
    {
        $final = $this->row('rehab_lahan');
        $this->row('rehab_lahan', ['status' => 'draft']);
        $this->row('rehab_lahan', ['status' => 'waiting_kasi']);
        $this->row('rehab_lahan', ['status' => 'final', 'deleted_at' => now()]);

        $response = $this->getAs($this->token(null), '/api/v1/rhl/rehab-lahan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('links.next', null)
            ->assertJsonStructure(['data', 'links' => ['next', 'prev'], 'meta' => ['current_page', 'last_page', 'per_page', 'total'], 'waktu_server']);

        // assertEquals: angka JSON 5 dan 5.0 setara bagi klien.
        $this->assertEquals([
            'id' => $final,
            'tahun' => 2026,
            'bulan' => 3,
            'cdk' => ['id' => $this->trenggalek, 'nama' => 'CDK Wilayah Trenggalek'],
            'wilayah' => [
                'provinsi' => ['id' => '35', 'nama' => 'JAWA TIMUR'],
                'kabupaten' => ['id' => '3503', 'nama' => 'KABUPATEN TRENGGALEK'],
                'kecamatan' => ['id' => '3503060', 'nama' => 'PULE'],
                'desa' => ['id' => '3503060009', 'nama' => 'KARANGANYAR'],
            ],
            'target_tahunan_ha' => 5.0,
            'realisasi_ha' => 4.5,
            'sumber_dana' => 'APBN',
            'koordinat' => '-8.1,111.7',
            'difinalkan_pada' => '2026-03-27T11:05:06+00:00',
            'dibuat_pada' => '2026-03-27T02:00:00+00:00',
            'diperbarui_pada' => '2026-03-27T11:05:06+00:00',
        ], $response->json('data.0'));
    }

    public function test_cdk_client_only_sees_its_cdk_even_when_filtering_another(): void
    {
        $own = $this->row('rehab_lahan');
        $other = $this->row('rehab_lahan', ['cdk_id' => $this->malang, 'regency_id' => '3507']);
        $token = $this->token($this->trenggalek);

        $this->assertSame([$own], $this->getAs($token, '/api/v1/rhl/rehab-lahan')->assertOk()->json('data.*.id'));
        $this->assertSame([], $this->getAs($token, "/api/v1/rhl/rehab-lahan?cdk_id={$this->malang}")->assertOk()->json('data'));
        $this->assertSame([$own, $other], $this->getAs($this->token(null), '/api/v1/rhl/rehab-lahan')->json('data.*.id'));
    }

    public function test_filters_pagination_and_incremental_sync(): void
    {
        $a = $this->row('rehab_lahan', ['updated_at' => '2026-03-01 00:00:00']);
        $b = $this->row('rehab_lahan', ['month' => 4, 'updated_at' => '2026-04-01 00:00:00']);
        $c = $this->row('rehab_lahan', ['year' => 2025, 'regency_id' => '3507', 'updated_at' => '2026-02-01 00:00:00']);
        $token = $this->token(null);

        // Urut dari yang paling lama diperbarui.
        $this->assertSame([$c, $a, $b], $this->getAs($token, '/api/v1/rhl/rehab-lahan')->json('data.*.id'));
        $this->assertSame([$a, $b], $this->getAs($token, '/api/v1/rhl/rehab-lahan?tahun=2026')->json('data.*.id'));
        $this->assertSame([$b], $this->getAs($token, '/api/v1/rhl/rehab-lahan?tahun=2026&bulan=4')->json('data.*.id'));
        $this->assertSame([$c], $this->getAs($token, '/api/v1/rhl/rehab-lahan?kabupaten_id=3507')->json('data.*.id'));
        // updated_since dengan zona waktu lain dikonversi ke waktu aplikasi.
        $this->assertSame([$b], $this->getAs($token, '/api/v1/rhl/rehab-lahan?updated_since=' . urlencode('2026-04-01T07:00:00+07:00'))->json('data.*.id'));

        $page1 = $this->getAs($token, '/api/v1/rhl/rehab-lahan?per_page=2')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2);
        $this->assertSame([$c, $a], $page1->json('data.*.id'));
        $this->assertStringContainsString('per_page=2', $page1->json('links.next'));
        $this->assertStringContainsString('page=2', $page1->json('links.next'));

        $this->assertSame([$b], $this->getAs($token, '/api/v1/rhl/rehab-lahan?per_page=2&page=2')->json('data.*.id'));
    }

    public function test_invalid_filters_return_422(): void
    {
        $token = $this->token(null);

        $this->getAs($token, '/api/v1/rhl/rehab-lahan?tahun=abc')->assertStatus(422)->assertJsonValidationErrors('tahun');
        $this->getAs($token, '/api/v1/rhl/rehab-lahan?bulan=13')->assertStatus(422)->assertJsonValidationErrors('bulan');
        $this->getAs($token, '/api/v1/rhl/rehab-lahan?per_page=501')->assertStatus(422)->assertJsonValidationErrors('per_page');
        $this->getAs($token, '/api/v1/rhl/rehab-lahan?updated_since=kemarin')->assertStatus(422)->assertJsonValidationErrors('updated_since');
        $this->getAs($token, '/api/v1/rhl/rehab-lahan/deleted')->assertStatus(422)->assertJsonValidationErrors('since');
    }

    public function test_token_without_rhl_ability_is_rejected(): void
    {
        $token = $this->token(null, ['perlindungan.read']);

        foreach (array_keys(self::DATASETS) as $dataset) {
            $this->getAs($token, "/api/v1/rhl/$dataset")->assertStatus(403);
            $this->getAs($token, "/api/v1/rhl/$dataset/deleted?since=2026-01-01")->assertStatus(403);
        }
    }

    public function test_every_dataset_returns_its_fields_without_internal_columns(): void
    {
        foreach (self::DATASETS as $table) {
            $this->row($table, $table === 'reboisasi_ps' ? ['pengelola_id' => 1] : []);
        }
        $teknis = DB::table('rhl_teknis')->value('id');
        DB::table('rhl_teknis_details')->insert(['rhl_teknis_id' => $teknis, 'bangunan_kta_id' => 1, 'unit_amount' => 3]);
        $token = $this->token($this->trenggalek);

        foreach (array_keys(self::DATASETS) as $dataset) {
            $row = $this->getAs($token, "/api/v1/rhl/$dataset")->assertOk()->assertJsonCount(1, 'data')->json('data.0');

            $this->assertSame('KABUPATEN TRENGGALEK', $row['wilayah']['kabupaten']['nama'], $dataset);
            $this->assertSame('CDK Wilayah Trenggalek', $row['cdk']['nama'], $dataset);
            foreach (['status', 'rejection_note', 'created_by', 'updated_by', 'deleted_by', 'cdk_id', 'regency_id'] as $internal) {
                $this->assertArrayNotHasKey($internal, $row, "$dataset: $internal");
            }
        }

        $this->assertSame(['id' => 1, 'nama' => 'KTH Lancar Jaya'], $this->getAs($token, '/api/v1/rhl/reboisasi-ps')->json('data.0.pengelola'));
        $teknisRow = $this->getAs($token, '/api/v1/rhl/rhl-teknis')->json('data.0');
        $this->assertEquals(5, $teknisRow['target_tahunan_unit']);
        $this->assertSame([['bangunan_kta' => ['id' => 1, 'nama' => 'Dam Penahan'], 'jumlah_unit' => 3]], $teknisRow['rincian']);
        $this->assertArrayNotHasKey('realisasi_ha', $teknisRow);
    }

    public function test_deleted_endpoint_lists_rows_that_left_the_api_since_given_time(): void
    {
        Carbon::setTestNow('2026-05-01 00:00:00');
        $this->row('rehab_lahan');
        $deleted = $this->row('rehab_lahan', ['deleted_at' => '2026-04-10 00:00:00']);
        $this->row('rehab_lahan', ['deleted_at' => '2026-01-01 00:00:00']);
        $rejected = $this->row('rehab_lahan', ['status' => 'rejected', 'updated_at' => '2026-04-12 00:00:00']);
        $this->row('rehab_lahan', ['cdk_id' => $this->malang, 'deleted_at' => '2026-04-10 00:00:00']);

        $response = $this->getAs($this->token($this->trenggalek), '/api/v1/rhl/rehab-lahan/deleted?since=2026-04-01T00:00:00Z')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('waktu_server', '2026-05-01T00:00:00+00:00');

        $this->assertSame([
            ['id' => $deleted, 'alasan' => 'dihapus', 'waktu' => '2026-04-10T00:00:00+00:00'],
            ['id' => $rejected, 'alasan' => 'tidak_final', 'waktu' => '2026-04-12T00:00:00+00:00'],
        ], $response->json('data'));
        Carbon::setTestNow();
    }
}
