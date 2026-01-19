<?php

namespace Database\Seeders;

use App\Models\PengelolaWisata;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PengelolaWisataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wisata = [
            ["name" => "Perum Perhutani"],
            ["name" => "KPH Kediri"],
            ["name" => "KPH Blitar"],
            ["name" => "Lancar Jaya (Kediri)"],
            ["name" => "Petani Hutan Sejahtera (Kediri)"],
            ["name" => "Argo Makmur Lestari (Tulungagung)"],
            ["name" => "Wono Rukun Sejati (Tulungagung)"],
            ["name" => "Wonodadi Lestari (Tulungagung)"],
            ["name" => "Rimba Madu Sejahtera (Trenggalek)"],
            ["name" => "Sido Rukun (Trenggalek)"],
            ["name" => "Tani Sentosa (Trenggalek)"],
            ["name" => "Wono Bangkit (Trenggalek)"],
            ["name" => "Sumber Lestari Dawuhan (Trenggalek)"],
            ["name" => "Rimbun Sumber Urip (Trenggalek)"],
            ["name" => "KHDPK (Belum Persetujuan)"]
        ];

        foreach ($wisata as $w) {
            PengelolaWisata::create($w);
        }
    }
}
