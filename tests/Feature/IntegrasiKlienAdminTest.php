<?php

namespace Tests\Feature;

use App\Models\IntegrationClient;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\Concerns\UsesIsolatedUserDatabase;
use Tests\TestCase;

class IntegrasiKlienAdminTest extends TestCase
{
    use UsesIsolatedUserDatabase { setUp as setUpUserDatabase; }

    private int $trenggalekId;
    private User $admin;

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

        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin_provinsi', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_only_admin_can_open_the_page(): void
    {
        $this->get(route('integrasi-klien.index'))->assertRedirect(route('login'));

        $adminProvinsi = User::factory()->create();
        $adminProvinsi->assignRole('admin_provinsi');
        $this->actingAs($adminProvinsi)
            ->from('/dashboard')
            ->get(route('integrasi-klien.index'))
            ->assertRedirect('/dashboard')
            ->assertSessionHas('error');

        $this->actingAs($adminProvinsi)
            ->from('/dashboard')
            ->post(route('integrasi-klien.store'), ['kode' => 'x', 'nama' => 'X', 'is_active' => true])
            ->assertRedirect('/dashboard');
        $this->assertDatabaseCount('integration_clients', 0);
    }

    public function test_index_lists_clients_tokens_usage_and_options(): void
    {
        $client = IntegrationClient::create(['kode' => 'cdk-trenggalek', 'nama' => 'Sistem CDK Trenggalek', 'cdk_id' => $this->trenggalekId]);
        $client->createToken('produksi', ['rhl.read'], now()->subDay());
        activity('integrasi')->causedBy($client)->event('api_request')->log('GET /api/v1/klien');

        $this->actingAs($this->admin)
            ->get(route('integrasi-klien.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('IntegrasiKlien/Index')
                ->has('clients', 1)
                ->where('clients.0.kode', 'cdk-trenggalek')
                ->where('clients.0.cdk_nama', 'CDK Wilayah Trenggalek')
                ->where('clients.0.requests_24h', 1)
                ->whereNot('clients.0.last_request_at', null)
                ->has('clients.0.tokens', 1)
                ->where('clients.0.tokens.0.abilities', ['rhl.read'])
                ->where('clients.0.tokens.0.is_expired', true)
                ->missing('clients.0.tokens.0.token')
                ->has('cdks', 1)
                ->has('abilities', count(config('integrasi.abilities')))
                ->where('rateLimitPerMinute', config('integrasi.rate_limit_per_minute')));
    }

    public function test_admin_can_create_update_and_delete_client(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('integrasi-klien.store'), [
            'kode' => 'Bukan Slug',
            'nama' => 'SIPETAK',
            'is_active' => true,
        ])->assertSessionHasErrors('kode');

        $this->post(route('integrasi-klien.store'), [
            'kode' => 'cdk-trenggalek',
            'nama' => 'Sistem CDK Trenggalek',
            'deskripsi' => 'Kontak: tim TI CDK',
            'cdk_id' => $this->trenggalekId,
            'is_active' => true,
        ])->assertRedirect(route('integrasi-klien.index'))->assertSessionHas('success');

        $client = IntegrationClient::where('kode', 'cdk-trenggalek')->firstOrFail();
        $this->assertSame($this->trenggalekId, $client->cdk_id);

        $this->post(route('integrasi-klien.store'), ['kode' => 'cdk-trenggalek', 'nama' => 'Ganda', 'is_active' => true])
            ->assertSessionHasErrors('kode');
        $this->post(route('integrasi-klien.store'), ['kode' => 'sipetak', 'nama' => 'SIPETAK', 'cdk_id' => 999, 'is_active' => true])
            ->assertSessionHasErrors('cdk_id');

        $this->put(route('integrasi-klien.update', $client), [
            'kode' => 'cdk-trenggalek',
            'nama' => 'Sistem CDK Trenggalek',
            'cdk_id' => null,
            'is_active' => false,
        ])->assertRedirect(route('integrasi-klien.index'));
        $client->refresh();
        $this->assertNull($client->cdk_id);
        $this->assertFalse($client->is_active);

        $client->createToken('produksi', ['rhl.read']);
        $this->delete(route('integrasi-klien.destroy', $client))->assertRedirect(route('integrasi-klien.index'));
        $this->assertDatabaseCount('integration_clients', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_can_issue_token_once_and_revoke_it(): void
    {
        $client = IntegrationClient::create(['kode' => 'cdk-trenggalek', 'nama' => 'Sistem CDK Trenggalek', 'cdk_id' => $this->trenggalekId]);
        $this->actingAs($this->admin);

        $this->post(route('integrasi-klien.tokens.store', $client), ['name' => 'produksi', 'abilities' => []])
            ->assertSessionHasErrors('abilities');
        $this->post(route('integrasi-klien.tokens.store', $client), ['name' => 'produksi', 'abilities' => ['hapus.semua']])
            ->assertSessionHasErrors('abilities.0');

        $this->post(route('integrasi-klien.tokens.store', $client), [
            'name' => 'produksi',
            'abilities' => ['rhl.read', 'wilayah.read'],
            'expires_in_days' => 90,
        ])->assertRedirect(route('integrasi-klien.index'))->assertSessionHas('new_token');

        $plain = session('new_token')['token'];
        $token = $client->tokens()->firstOrFail();
        $this->assertSame(['rhl.read', 'wilayah.read'], $token->abilities);
        $this->assertTrue($token->expires_at->between(now()->addDays(89), now()->addDays(91)));
        $this->assertNotSame($plain, $token->token, 'Token mentah tidak boleh tersimpan di database');

        $log = DB::table('activity_log')->where('event', 'token_created')->first();
        $this->assertSame(User::class, $log->causer_type);
        $this->assertEquals($this->admin->id, $log->causer_id);

        // Token yang diterbitkan dari UI langsung bisa dipakai di API.
        $this->app['auth']->forgetGuards();
        $this->withToken($plain)->getJson('/api/v1/klien')->assertOk()->assertJsonPath('data.kode', 'cdk-trenggalek');

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin);
        $other = IntegrationClient::create(['kode' => 'sipetak', 'nama' => 'SIPETAK']);
        $this->delete(route('integrasi-klien.tokens.destroy', [$other, $token->id]))->assertNotFound();
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->delete(route('integrasi-klien.tokens.destroy', [$client, $token->id]))
            ->assertRedirect(route('integrasi-klien.index'));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(DB::table('activity_log')->where('event', 'token_revoked')->exists());
    }
}
