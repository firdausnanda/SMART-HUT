<?php

namespace Database\Factories;

use App\Models\BukanKayu;
use App\Models\HasilHutanBukanKayu;
use App\Models\User;
use App\Models\Villages;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HasilHutanBukanKayu>
 */
class HasilHutanBukanKayuFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    $village = Villages::whereHas('district.regency', function ($q) {
      $q->where('province_id', 35)->whereIn('name', [
        'KABUPATEN TRENGGALEK',
        'KABUPATEN TULUNGAGUNG',
        'KABUPATEN KEDIRI',
        'KOTA KEDIRI'
      ]);
    })->inRandomOrder()->first();

    $district = $village->district;
    $regency = $district->regency;
    $bukanKayu = BukanKayu::inRandomOrder()->first();
    $user = User::inRandomOrder()->first();

    return [
      'year' => 2026,
      'month' => fake()->month(),
      'province_id' => 35,
      'regency_id' => $regency->id,
      'district_id' => $district->id,
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
    return $this->afterCreating(function (HasilHutanBukanKayu $hhbk) {
      $commodities = \App\Models\Commodity::inRandomOrder()->limit(rand(1, 3))->get();
      foreach ($commodities as $commodity) {
        $hhbk->details()->create([
          'commodity_id' => $commodity->id,
          'annual_volume_realization' => fake()->randomFloat(2, 40, 900),
          'unit' => fake()->randomElement(['Kg', 'Ton', 'Ltr']),
        ]);
      }
    });
  }
}
