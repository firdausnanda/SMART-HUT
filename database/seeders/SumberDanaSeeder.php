<?php

namespace Database\Seeders;

use App\Models\SumberDana;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SumberDanaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SumberDana::create([
            'name' => 'APBN'
        ]);

        SumberDana::create([
            'name' => 'APBD Provinsi'
        ]);

        SumberDana::create([
            'name' => 'APBD Kabupaten/Kota'
        ]);

        SumberDana::create([
            'name' => 'BUMS'
        ]);

        SumberDana::create([
            'name' => 'CSR'
        ]);

        SumberDana::create([
            'name' => 'BPDLH'
        ]);

        SumberDana::create([
            'name' => 'BUMN - PJT'
        ]);

        SumberDana::create([
            'name' => 'Swadaya'
        ]);

        SumberDana::create([
            'name' => 'Lainnya'
        ]);

    }
}
