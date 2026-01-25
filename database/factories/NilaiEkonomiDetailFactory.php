<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NilaiEkonomiDetail>
 */
class NilaiEkonomiDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commodity_id' => \App\Models\Commodity::inRandomOrder()->first()->id ?? \App\Models\Commodity::factory(),
            'production_volume' => $this->faker->numberBetween(100, 5000),
            'transaction_value' => $this->faker->numberBetween(1000000, 50000000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
