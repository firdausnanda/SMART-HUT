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
    $kayu = Kayu::inRandomOrder()->first();
    $user = User::inRandomOrder()->first();

    return [
      'year' => 2026,
      'month' => fake()->month(),
      'province_id' => 35,
      'regency_id' => $regency->id,
      'district_id' => $district->id,
      'forest_type' => fake()->randomElement(['Hutan Rakyat', 'Hutan Negara']),
      'status' => 'final',
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
          'volume_target' => fake()->randomFloat(2, 10, 1000),
          'volume_realization' => fake()->randomFloat(2, 10, 1000),
        ]);
      }
    });
  }
}
