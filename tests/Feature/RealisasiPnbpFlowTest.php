<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RealisasiPnbp;
use Tests\Concerns\UsesIsolatedForestryDatabase;
use Tests\TestCase;

class RealisasiPnbpFlowTest extends TestCase
{
  use UsesIsolatedForestryDatabase;

  public function test_can_view_index()
  {
    $user = User::factory()->create();
    $user->givePermissionTo('realisasi-pnbp.view');

    $response = $this->actingAs($user)->get(route('realisasi-pnbp.index'));
    $response->assertStatus(200);
  }

  public function test_can_create_realisasi_pnbp()
  {
    $user = User::factory()->create();
    $user->givePermissionTo('realisasi-pnbp.create');

    $pengelola = \App\Models\PengelolaWisata::factory()->create(['name' => 'Perhutani']);

    $response = $this->actingAs($user)->post(route('realisasi-pnbp.store'), [
      'year' => 2026,
      'month' => 5,
      'province_id' => 35,
      'regency_id' => 3501,
      'id_pengelola_wisata' => $pengelola->id,
      'types_of_forest_products' => 'Kayu Jati',
      'pnbp_target' => '1000000',
      'pnbp_realization' => '500000',
    ]);

    $response->assertRedirect(route('realisasi-pnbp.index'));
    $this->assertDatabaseHas('realisasi_pnbp', [
      'id_pengelola_wisata' => $pengelola->id,
      'pnbp_realization' => '500000',
    ]);
  }

  public function test_can_update_realisasi_pnbp()
  {
    $user = User::factory()->create();
    $user->givePermissionTo('realisasi-pnbp.edit');

    $item = RealisasiPnbp::factory()->create();
    $pengelola = \App\Models\PengelolaWisata::factory()->create(['name' => 'Masyarakat']);

    $response = $this->actingAs($user)->patch(route('realisasi-pnbp.update', $item->id), [
      'year' => 2026,
      'month' => 6,
      'province_id' => 35,
      'regency_id' => $item->regency_id,
      'id_pengelola_wisata' => $pengelola->id,
      'types_of_forest_products' => 'Rotan',
      'pnbp_target' => '2000000',
      'pnbp_realization' => '1500000',
    ]);

    $response->assertRedirect(route('realisasi-pnbp.index'));
    $this->assertDatabaseHas('realisasi_pnbp', [
      'id' => $item->id,
      'id_pengelola_wisata' => $pengelola->id,
      'pnbp_realization' => '1500000',
    ]);
  }
}
