<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NilaiEkonomiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure some commodities exist first
        if (\App\Models\Commodity::count() == 0) {
            \App\Models\Commodity::insert([
                ['name' => 'Kopi'],
                ['name' => 'Madu'],
                ['name' => 'Coklat'],
                ['name' => 'Rotan'],
                ['name' => 'Bambu'],
                ['name' => 'Getah Pinus'],
                ['name' => 'Aren'],
                ['name' => 'Jati'],
                ['name' => 'Sengon'],
                ['name' => 'Porang'],
            ]);
        }

        \App\Models\NilaiEkonomi::factory()->count(100)->create()->each(function ($nilaiEkonomi) {
            $numCommodities = rand(1, 3);
            $totalValue = 0;

            for ($i = 0; $i < $numCommodities; $i++) {
                $commodity = \App\Models\Commodity::inRandomOrder()->first();
                $value = rand(1000000, 50000000);

                \App\Models\NilaiEkonomiDetail::create([
                    'nilai_ekonomi_id' => $nilaiEkonomi->id,
                    'commodity_id' => $commodity->id,
                    'production_volume' => rand(100, 5000),
                    'transaction_value' => $value,
                ]);

                $totalValue += $value;
            }

            $nilaiEkonomi->update(['total_transaction_value' => $totalValue]);
        });
    }
}
