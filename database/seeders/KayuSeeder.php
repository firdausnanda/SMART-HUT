<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Kayu;

class KayuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisKayu = [
            'Jati',
            'Sengon',
            'Mahoni',
            'Gmelina',
            'Akasia',
            'Sonokeling',
            'Pinus',
            'Balsa',
            'Kayu Lainnya'
        ];

        foreach ($jenisKayu as $nama) {
            Kayu::firstOrCreate([
                'name' => $nama
            ]);
        }
    }
}
