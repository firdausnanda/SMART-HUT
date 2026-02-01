<?php

namespace Database\Seeders;

use App\Models\Pbphh;
use Illuminate\Database\Seeder;

class PbphhSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    Pbphh::factory(500)->create()->each(function ($pbphh) {
      $jenis = \App\Models\JenisProduksi::inRandomOrder()->first();
      if ($jenis) {
        $pbphh->jenisProduksi()->attach($jenis->id, ['kapasitas_ijin' => fake()->numberBetween(100, 1000)]);
      }
    });
  }
}
