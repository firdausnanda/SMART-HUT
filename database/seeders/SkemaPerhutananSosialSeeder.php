<?php

namespace Database\Seeders;

use App\Models\SkemaPerhutananSosial;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SkemaPerhutananSosialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            "Hutan Desa (HD)",
            "Hutan Kemasyarakatan (HKm)",
            "Hutan Tanaman Rakyat (HTR)",
            "Hutan Adat (HA)",
            "Kemitraan Kehutanan (KK)"
        ];

        foreach ($data as $item) {
            SkemaPerhutananSosial::create([
                'name' => $item,
            ]);
        }
    }
}
