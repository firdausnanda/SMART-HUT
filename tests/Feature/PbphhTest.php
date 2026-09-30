<?php

namespace Tests\Feature;

use App\Models\Pbphh;
use App\Models\User;
use Tests\Concerns\UsesIsolatedForestryDatabase;
use Tests\TestCase;

class PbphhTest extends TestCase
{
    use UsesIsolatedForestryDatabase;

    private function actingAsAdmin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);
    }

    private function payload(): array
    {
        return [
            'name' => 'PBPHH Uji',
            'number' => 'PBPHH-123',
            'province_id' => 35,
            'regency_id' => 3501,
            'district_id' => 350101,
            'investment_value' => 100000000,
            'number_of_workers' => 50,
            'present_condition' => true,
            'jenis_produksi' => [
                ['jenis_produksi_id' => 1, 'kapasitas_ijin' => 12.5],
            ],
        ];
    }

    public function test_index_page_is_accessible(): void
    {
        $this->actingAsAdmin();

        $this->get(route('pbphh.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Pbphh/Index'));
    }

    public function test_create_page_is_accessible(): void
    {
        $this->actingAsAdmin();

        $this->get(route('pbphh.create'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Pbphh/Create'));
    }

    public function test_can_create_pbphh_data(): void
    {
        $this->actingAsAdmin();

        $this->post(route('pbphh.store'), $this->payload())
            ->assertRedirect(route('pbphh.index'));

        $pbphh = Pbphh::firstOrFail();
        $this->assertSame('PBPHH-123', $pbphh->number);
        $this->assertDatabaseHas('pbphh_jenis_produksi', [
            'pbphh_id' => $pbphh->id,
            'jenis_produksi_id' => 1,
            'kapasitas_ijin' => 12.5,
        ]);
    }

    public function test_can_update_pbphh_data(): void
    {
        $this->actingAsAdmin();
        $pbphh = Pbphh::create(collect($this->payload())->except('jenis_produksi')->all());

        $this->put(route('pbphh.update', $pbphh), [
            ...$this->payload(),
            'name' => 'PBPHH Diperbarui',
            'number_of_workers' => 60,
            'present_condition' => false,
            'jenis_produksi' => [
                ['jenis_produksi_id' => 1, 'kapasitas_ijin' => 20],
            ],
        ])->assertRedirect(route('pbphh.index'));

        $this->assertDatabaseHas('pbphh', [
            'id' => $pbphh->id,
            'name' => 'PBPHH Diperbarui',
            'number_of_workers' => 60,
            'present_condition' => 0,
        ]);
        $this->assertDatabaseHas('pbphh_jenis_produksi', [
            'pbphh_id' => $pbphh->id,
            'kapasitas_ijin' => 20,
        ]);
    }

    public function test_can_delete_pbphh_data(): void
    {
        $this->actingAsAdmin();
        $pbphh = Pbphh::create(collect($this->payload())->except('jenis_produksi')->all());

        $this->delete(route('pbphh.destroy', $pbphh))
            ->assertRedirect(route('pbphh.index'));
        $this->assertSoftDeleted('pbphh', ['id' => $pbphh->id]);
    }
}
