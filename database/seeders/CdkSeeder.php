<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CdkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cdks = [
            [
                'kode' => 'CDK-TRG',
                'nama' => 'CDK Wilayah Trenggalek',
                'wilayah_kerja' => 'Trenggalek, Tulungagung, Kediri',
                'kepala_nama' => '-',
                'alamat' => 'Jl. Raya Trenggalek-Ponorogo No. 12',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK-MLG',
                'nama' => 'CDK Wilayah Malang',
                'wilayah_kerja' => 'Malang, Batu',
                'kepala_nama' => 'Ir. Budi Santoso, M.P',
                'alamat' => 'Jl. Jenderal Sudirman No. 45, Malang',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK-PSR',
                'nama' => 'CDK Wilayah Pacitan',
                'wilayah_kerja' => 'Pasuruan, Probolinggo',
                'kepala_nama' => 'H. Ahmad Fauzi, S.Hut, T',
                'alamat' => 'Jl. Pahlawan No. 8, Pasuruan',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK-BDW',
                'nama' => 'CDK Bondowoso',
                'wilayah_kerja' => 'Bondowoso, Situbondo',
                'kepala_nama' => 'Hendra Wijaya, S.E, M.M',
                'alamat' => 'Jl. Letnan Karsono No. 3, Bondowoso',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK-JMB',
                'nama' => 'CDK Jember',
                'wilayah_kerja' => 'Jember',
                'kepala_nama' => 'Siti Aminah, M.Agr',
                'alamat' => 'Jl. Gajah Mada No. 102, Jember',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK-BWI',
                'nama' => 'CDK Banyuwangi',
                'wilayah_kerja' => 'Banyuwangi',
                'kepala_nama' => 'Agus Setiawan, S.Hut',
                'alamat' => 'Jl. S. Parman No. 56, Banyuwangi',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK-BLT',
                'nama' => 'CDK Blitar',
                'wilayah_kerja' => 'Blitar, Kediri, Tulungagung',
                'kepala_nama' => 'Rony Wijaya, M.Si',
                'alamat' => 'Jl. Sudanco Supriadi No. 17, Blitar',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK-NGW',
                'nama' => 'CDK Ngawi',
                'wilayah_kerja' => 'Ngawi, Magetan, Madiun',
                'kepala_nama' => 'Eko Prasetyo, S.Hut, M.Si',
                'alamat' => 'Jl. Jenderal Basuki Rahmat No. 9, Ngawi',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK-BJN',
                'nama' => 'CDK Bojonegoro',
                'wilayah_kerja' => 'Bojonegoro, Tuban',
                'kepala_nama' => 'Dwi Cahyo, M.M',
                'alamat' => 'Jl. Panglima Polim No. 24, Bojonegoro',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK-LMG',
                'nama' => 'CDK Lamongan',
                'wilayah_kerja' => 'Lamongan, Gresik',
                'kepala_nama' => 'Ir. Totok Hermawan',
                'alamat' => 'Jl. Lamongrejo No. 15, Lamongan',
                'is_active' => true,
            ],
        ];

        foreach ($cdks as $cdkData) {
            $wilayahKerjas = explode(', ', $cdkData['wilayah_kerja']);
            unset($cdkData['wilayah_kerja']);

            DB::table('cdks')->updateOrInsert(
                ['kode' => $cdkData['kode']],
                array_merge($cdkData, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );

            $cdk = DB::table('cdks')->where('kode', $cdkData['kode'])->first();

            DB::table('cdk_regency')->where('cdk_id', $cdk->id)->delete();

            foreach ($wilayahKerjas as $wilayah) {
                $regencyName = trim($wilayah);

                // Get matching regencies (e.g. both Kota and Kabupaten if applicable)
                $regencies = DB::table('m_regencies')
                    ->where('name', 'LIKE', '%' . strtoupper($regencyName) . '%')
                    ->get();

                foreach ($regencies as $regency) {
                    DB::table('cdk_regency')->updateOrInsert([
                        'cdk_id' => $cdk->id,
                        'regency_id' => $regency->id,
                    ]);
                }
            }
        }
    }
}
