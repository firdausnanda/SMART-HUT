<?php

namespace Database\Factories;

use App\Models\Kayu;
use App\Models\User;
use App\Models\Villages;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HasilHutanKayu>
 */
class HasilHutanKayuFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    // Simplified location logic since district is removed
    $regency_name = fake()->randomElement([
      'KABUPATEN TRENGGALEK',
      'KABUPATEN TULUNGAGUNG',
      'KABUPATEN KEDIRI',
      'KOTA KEDIRI'
    ]);

    $regency = \App\Models\Regencies::where('name', $regency_name)
      ->where('province_id', 35)
      ->first();

    // Fallback if not found (should generally exist if seeded)
    if (!$regency) {
      $regency = \App\Models\Regencies::where('province_id', 35)->inRandomOrder()->first();
    }

    $district = \App\Models\Districts::where('regency_id', $regency->id)->inRandomOrder()->first();

    $pengelola = \App\Models\PengelolaHutan::firstOrCreate(['name' => fake()->company() . ' Forest Manager']);
    $user = User::inRandomOrder()->first();

    return [
      'year' => 2026,
      'month' => fake()->month(),
      'province_id' => 35,
      'regency_id' => $regency->id,
      'district_id' => $district?->id,
      'pengelola_hutan_id' => $pengelola->id,
      'forest_type' => fake()->randomElement(['Hutan Rakyat', 'Hutan Negara']),
      'volume_target' => fake()->randomFloat(2, 50, 2000),
      'status' => fake()->randomElement(['draft', 'waiting_kasi', 'waiting_cdk', 'final', 'rejected']),
      'approved_by_kasi_at' => now(),
      'approved_by_cdk_at' => now(),
      'created_by' => $user->id,
      'updated_by' => $user->id,
    ];
  }

  public function configure()
  {
    return $this->afterCreating(function (\App\Models\HasilHutanKayu $hasilHutanKayu) {
      $kayus = Kayu::inRandomOrder()->limit(rand(1, 3))->get();
      foreach ($kayus as $kayu) {
        $hasilHutanKayu->details()->create([
          'kayu_id' => $kayu->id,
          'volume_realization' => fake()->randomFloat(2, 10, 1000),
        ]);
      }
    });
  }
}
