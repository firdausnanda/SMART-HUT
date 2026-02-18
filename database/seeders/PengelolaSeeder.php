<?php

namespace Database\Seeders;

use App\Models\PengelolaPS;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PengelolaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PengelolaPS::create([
            'name' => 'KTH Lancar Jaya (Kediri)'
        ]);

        PengelolaPS::create([
            'name' => 'KTH Mitra Tani Sejahtera (Kediri)'
        ]);

        PengelolaPS::create([
            'name' => 'KTH Petani Hutan Sejahtera (Kediri)'
        ]);

        PengelolaPS::create([
            'name' => 'KTH Lingkungan Hidup Sejahtera (Kediri)'
        ]);
    }
}
