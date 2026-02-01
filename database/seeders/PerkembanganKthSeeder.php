<?php

namespace Database\Seeders;

use App\Models\PerkembanganKth;
use Illuminate\Database\Seeder;

class PerkembanganKthSeeder extends Seeder
{
  public function run(): void
  {
    PerkembanganKth::factory()->count(500)->create();
  }
}
