<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NilaiEkonomi>
 */
class NilaiEkonomiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $province = \App\Models\Provinces::inRandomOrder()->first() ?? \App\Models\Provinces::factory()->create();
        $regency = \App\Models\Regencies::where('province_id', $province->id)->inRandomOrder()->first() ?? \App\Models\Regencies::factory()->create(['province_id' => $province->id]);
        $district = \App\Models\Districts::where('regency_id', $regency->id)->inRandomOrder()->first() ?? \App\Models\Districts::factory()->create(['regency_id' => $regency->id]);

        return [
            'nama_kelompok' => $this->faker->company . ' Cluster',
            'total_transaction_value' => 0,
            'year' => $this->faker->numberBetween(2023, 2025),
            'month' => $this->faker->numberBetween(1, 12),
            'province_id' => $province->id,
            'regency_id' => $regency->id,
            'district_id' => $district->id,
            'status' => $this->faker->randomElement(['draft', 'approved_by_kasi', 'approved_by_cdk', 'rejected']),
            'created_by' => 1,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (\App\Models\NilaiEkonomi $nilaiEkonomi) {
            // Create 1-3 details
            $details = \App\Models\NilaiEkonomiDetail::factory()
                ->count(rand(1, 3))
                ->create(['nilai_ekonomi_id' => $nilaiEkonomi->id]);

            // Update total
            $nilaiEkonomi->update([
                'total_transaction_value' => $details->sum('transaction_value')
            ]);
        });
    }
}
