<?php

namespace Database\Seeders;

use App\Models\PengelolaHutan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PengelolaHutanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'BKPH Pare',
            'BKPH Kediri',
            'BKPH Pace',
            'BKPH Tulungagung',
            'BKPH Trenggalek',
            'BKPH Karangan',
            'BKPH Bandung',
            'BKPH Kampak',
            'BKPH Dongko',
            'BKPH Dongko',
            'BKPH Campurdarat',
            'BKPH Kalidawir',
            'BKPH Rejotangan',
            'KPH Kediri',
            'KPH Blitar',
        ];

        foreach ($data as $item) {
            PengelolaHutan::firstOrCreate(['name' => $item]);
        }
    }
}
