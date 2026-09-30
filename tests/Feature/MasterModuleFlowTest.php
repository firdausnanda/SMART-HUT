<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\UsesIsolatedUserDatabase;
use Tests\TestCase;

class MasterModuleFlowTest extends TestCase
{
    use UsesIsolatedUserDatabase { setUp as setUpUserDatabase; }

    protected function setUp(): void
    {
        $this->setUpUserDatabase();

        Schema::create('m_provinces', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name');
        });
        Schema::create('m_commodities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_nilai_transaksi_ekonomi')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (['provinces.view', 'provinces.create', 'provinces.edit', 'provinces.delete', 'commodities.create'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function test_province_access_and_crud_keep_existing_routes(): void
    {
        $this->get(route('provinces.index'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user);
        $this->from('/dashboard')->get(route('provinces.index'))
            ->assertRedirect('/dashboard')->assertSessionHas('error');

        $user->givePermissionTo(['provinces.view', 'provinces.create', 'provinces.edit', 'provinces.delete']);
        $this->get(route('provinces.index'))->assertOk();

        $this->post(route('provinces.store'), ['id' => 55, 'name' => 'Provinsi Uji'])
            ->assertRedirect(route('provinces.index'));
        $this->assertDatabaseHas('m_provinces', ['id' => 55, 'name' => 'Provinsi Uji']);

        $this->put(route('provinces.update', 55), ['name' => 'Provinsi Diperbarui'])
            ->assertRedirect(route('provinces.index'));
        $this->assertDatabaseHas('m_provinces', ['id' => 55, 'name' => 'Provinsi Diperbarui']);

        $this->delete(route('provinces.destroy', 55))
            ->assertRedirect(route('provinces.index'));
        $this->assertDatabaseMissing('m_provinces', ['id' => 55]);
    }

    public function test_commodity_json_creation_keeps_existing_response(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('commodities.create');
        $this->actingAs($user);

        $this->postJson(route('commodities.store'), ['name' => '  kayu   jati  '])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('commodity.name', 'Kayu Jati');
        $this->assertDatabaseHas('m_commodities', [
            'name' => 'Kayu Jati',
            'is_nilai_transaksi_ekonomi' => 0,
        ]);
    }
}
