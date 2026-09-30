<?php

namespace Tests\Feature;

use App\Models\RealisasiPnbp;
use App\Models\User;
use Tests\Concerns\UsesIsolatedForestryDatabase;
use Tests\TestCase;

class RealisasiPnbpTest extends TestCase
{
  use UsesIsolatedForestryDatabase;

  public function test_can_create_realisasi_pnbp_with_factory()
  {
    User::factory()->create();
    $realisasi = RealisasiPnbp::factory()->create();

    $this->assertDatabaseHas('realisasi_pnbp', [
      'id' => $realisasi->id,
      'province_id' => 35,
    ]);

    $this->assertNotNull($realisasi->id_pengelola_wisata);
    $this->assertNotNull($realisasi->pnbp_realization);
  }
}
