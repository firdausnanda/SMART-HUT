<?php

namespace Tests\Feature;

use App\Models\IntegrationClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesIsolatedUserDatabase;
use Tests\TestCase;

class IntegrasiApiFoundationTest extends TestCase
{
    use UsesIsolatedUserDatabase { setUp as setUpUserDatabase; }

    private int $trenggalekId;
    private int $malangId;

    protected function setUp(): void
    {
        $this->setUpUserDatabase();

        Schema::create('cdks', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->timestamps();
        });
        (require base_path('vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        (require base_path('database/migrations/2026_11_01_000001_create_integration_clients_table.php'))->up();

        $this->trenggalekId = DB::table('cdks')->insertGetId(['kode' => 'CDK Wilayah Trenggalek', 'nama' => 'CDK Wilayah Trenggalek']);
        $this->malangId = DB::table('cdks')->insertGetId(['kode' => 'CDK Wilayah Malang', 'nama' => 'CDK Wilayah Malang']);

        // Endpoint uji yang memakai stack middleware /api/v1 ditambah ability.
        Route::middleware(['api', 'auth:sanctum', 'integrasi.client', 'throttle:integrasi', 'integrasi.log', 'ability:rhl.read'])
            ->get('api/v1/_uji-ability', fn () => ['ok' => true]);
    }

    private function client(array $attributes = []): IntegrationClient
    {
        return IntegrationClient::create(array_merge([
            'kode' => 'cdk-trenggalek',
            'nama' => 'Sistem CDK Trenggalek',
            'cdk_id' => $this->trenggalekId,
        ], $attributes));
    }

    public function test_request_without_token_gets_json_401_even_without_accept_header(): void
    {
        $this->get('/api/v1/klien')
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_client_endpoint_returns_identity_scope_and_token_abilities(): void
    {
        $token = $this->client()->createToken('produksi', ['rhl.read', 'wilayah.read'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/klien')
            ->assertOk()
            ->assertJsonPath('data.kode', 'cdk-trenggalek')
            ->assertJsonPath('data.cakupan', 'cdk')
            ->assertJsonPath('data.cdk.id', $this->trenggalekId)
            ->assertJsonPath('data.token.nama', 'produksi')
            ->assertJsonPath('data.token.abilities', ['rhl.read', 'wilayah.read'])
            ->assertJsonPath('data.token.kedaluwarsa', null);
    }

    public function test_province_wide_client_has_no_cdk(): void
    {
        $token = $this->client(['kode' => 'sipetak', 'nama' => 'SIPETAK', 'cdk_id' => null])
            ->createToken('produksi', ['rhl.read'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/klien')
            ->assertOk()
            ->assertJsonPath('data.cakupan', 'provinsi')
            ->assertJsonPath('data.cdk', null);
    }

    public function test_inactive_client_is_rejected(): void
    {
        $client = $this->client();
        $token = $client->createToken('produksi', ['rhl.read'])->plainTextToken;
        $client->update(['is_active' => false]);

        $this->withToken($token)->getJson('/api/v1/klien')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Klien integrasi dinonaktifkan.');
    }

    public function test_expired_and_revoked_tokens_are_rejected(): void
    {
        $client = $this->client();
        $expired = $client->createToken('lama', ['rhl.read'], now()->subMinute())->plainTextToken;
        $revoked = $client->createToken('dicabut', ['rhl.read']);
        $revoked->accessToken->delete();

        $this->withToken($expired)->getJson('/api/v1/klien')->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($revoked->plainTextToken)->getJson('/api/v1/klien')->assertStatus(401);
    }

    public function test_token_without_required_ability_gets_403(): void
    {
        $client = $this->client();
        $withAbility = $client->createToken('a', ['rhl.read'])->plainTextToken;
        $withoutAbility = $client->createToken('b', ['wilayah.read'])->plainTextToken;

        $this->withToken($withAbility)->getJson('/api/v1/_uji-ability')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($withoutAbility)->getJson('/api/v1/_uji-ability')->assertStatus(403);
    }

    public function test_rate_limit_is_per_client_and_returns_json_429(): void
    {
        config(['integrasi.rate_limit_per_minute' => 2]);
        $token = $this->client()->createToken('produksi', ['rhl.read'])->plainTextToken;
        $other = $this->client(['kode' => 'sipetak', 'nama' => 'SIPETAK', 'cdk_id' => null])
            ->createToken('produksi', ['rhl.read'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/klien')->assertOk();
        $this->withToken($token)->getJson('/api/v1/klien')->assertOk();
        $this->withToken($token)->getJson('/api/v1/klien')
            ->assertStatus(429)
            ->assertHeader('Retry-After');

        $this->app['auth']->forgetGuards();
        $this->withToken($other)->getJson('/api/v1/klien')->assertOk();
    }

    public function test_each_request_is_logged_to_activity_log(): void
    {
        $client = $this->client();
        $token = $client->createToken('produksi', ['rhl.read']);

        $this->withToken($token->plainTextToken)->getJson('/api/v1/klien?tahun=2026')->assertOk();

        $log = DB::table('activity_log')->where('log_name', 'integrasi')->where('event', 'api_request')->first();
        $this->assertNotNull($log);
        $this->assertSame(IntegrationClient::class, $log->causer_type);
        $this->assertEquals($client->id, $log->causer_id);
        $properties = json_decode($log->properties, true);
        $this->assertSame('api/v1/klien', $properties['path']);
        $this->assertSame(['tahun' => '2026'], $properties['query']);
        $this->assertSame(200, $properties['status']);
        $this->assertSame($token->accessToken->id, $properties['token_id']);
    }

    public function test_data_scope_returns_only_final_rows_of_client_cdk(): void
    {
        Schema::create('contoh_data', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->unsignedBigInteger('cdk_id')->nullable();
        });
        DB::table('contoh_data')->insert([
            ['status' => 'final', 'cdk_id' => $this->trenggalekId],
            ['status' => 'draft', 'cdk_id' => $this->trenggalekId],
            ['status' => 'waiting_kasi', 'cdk_id' => $this->trenggalekId],
            ['status' => 'final', 'cdk_id' => $this->malangId],
        ]);
        $model = new class extends Model {
            protected $table = 'contoh_data';
        };

        $cdkClient = $this->client();
        $provinceClient = $this->client(['kode' => 'sipetak', 'nama' => 'SIPETAK', 'cdk_id' => null]);

        $this->assertSame([1], $cdkClient->applyDataScope($model->newQuery())->pluck('id')->all());
        $this->assertSame([1, 4], $provinceClient->applyDataScope($model->newQuery())->orderBy('id')->pluck('id')->all());
        // Filter cdk_id dari klien tidak bisa membuka data CDK lain.
        $this->assertSame([], $cdkClient->applyDataScope($model->newQuery())->where('cdk_id', $this->malangId)->pluck('id')->all());
    }

    public function test_commands_create_client_issue_and_revoke_tokens(): void
    {
        $this->artisan('integrasi:klien-buat', [
            'kode' => 'cdk-trenggalek',
            'nama' => 'Sistem CDK Trenggalek',
            '--cdk' => 'CDK Wilayah Trenggalek',
        ])->assertSuccessful();

        $client = IntegrationClient::where('kode', 'cdk-trenggalek')->firstOrFail();
        $this->assertSame($this->trenggalekId, $client->cdk_id);

        $this->artisan('integrasi:klien-buat', ['kode' => 'x', 'nama' => 'X', '--cdk' => 'Tidak Ada'])->assertFailed();
        $this->artisan('integrasi:token-buat', ['kode' => 'cdk-trenggalek'])->assertFailed();
        $this->artisan('integrasi:token-buat', ['kode' => 'cdk-trenggalek', '--ability' => ['hapus.semua']])->assertFailed();
        $this->artisan('integrasi:token-buat', ['kode' => 'cdk-trenggalek', '--ability' => ['rhl.read'], '--hari' => '0'])->assertFailed();

        $client->createToken('a', ['rhl.read']);
        $client->createToken('b', ['rhl.read']);
        $this->artisan('integrasi:token-cabut', ['kode' => 'cdk-trenggalek'])->assertFailed();
        $this->artisan('integrasi:token-cabut', ['kode' => 'cdk-trenggalek', '--semua' => true])->assertSuccessful();
        $this->assertSame(0, $client->tokens()->count());
        $this->assertTrue(DB::table('activity_log')->where('event', 'token_revoked')->exists());

        $this->artisan('integrasi:klien-status', ['kode' => 'cdk-trenggalek', 'status' => 'nonaktif'])->assertSuccessful();
        $this->assertFalse($client->fresh()->is_active);
    }

    public function test_token_command_prints_a_working_token_once(): void
    {
        $client = $this->client();

        Artisan::call('integrasi:token-buat', [
            'kode' => 'cdk-trenggalek',
            '--ability' => ['rhl.read', 'rhl.read'],
            '--nama' => 'produksi',
            '--hari' => '30',
        ]);
        $this->assertSame(1, preg_match('/^(\d+\|[A-Za-z0-9]+)\s*$/m', Artisan::output(), $match), 'Token tidak tercetak');

        $token = $client->tokens()->firstOrFail();
        $this->assertSame(['rhl.read'], $token->abilities);
        $this->assertTrue($token->expires_at->between(now()->addDays(29), now()->addDays(31)));
        $this->assertTrue(DB::table('activity_log')->where('event', 'token_created')->exists());

        $this->withToken($match[1])->getJson('/api/v1/klien')->assertOk();
    }

    public function test_user_api_route_keeps_its_own_throttle(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())->first(fn ($route) => $route->uri() === 'api/user');

        $this->assertContains('throttle:api', $route->gatherMiddleware());
    }
}
