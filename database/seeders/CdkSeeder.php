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
                'kode' => 'CDK Wilayah Pacitan',
                'nama' => 'CDK Wilayah Pacitan',
                'wilayah_kerja' => 'Pacitan, Ponorogo',
                'kepala_nama' => '-',
                'alamat' => 'Jl. Brawijaya, Balong, Sidoharjo, Kec. Pacitan, Kabupaten Pacitan, Jawa Timur',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK Wilayah Madiun',
                'nama' => 'CDK Wilayah Madiun',
                'wilayah_kerja' => 'Madiun, Magetan, Ngawi',
                'kepala_nama' => '-',
                'alamat' => 'Jl. Mayor Jend. Di Panjaitan, Banjarejo, Kec. Taman, Kota Madiun, Jawa Timur',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK Wilayah Trenggalek',
                'nama' => 'CDK Wilayah Trenggalek',
                'wilayah_kerja' => 'Trenggalek, Tulungagung, Kediri',
                'kepala_nama' => '-',
                'alamat' => 'Gg. Menak Sopal, Sukobanteng, Karangsoko, Kec. Trenggalek, Kabupaten Trenggalek, Jawa Timur 66319',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK Wilayah Malang',
                'nama' => 'CDK Wilayah Malang',
                'wilayah_kerja' => 'Malang, Batu, Blitar',
                'kepala_nama' => '-',
                'alamat' => 'Jl. Raya Genengan No.9,3, Dusun Binangun, Genengan, Kec. Pakisaji, Kabupaten Malang, Jawa Timur',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK Wilayah Nganjuk',
                'nama' => 'CDK Wilayah Nganjuk',
                'wilayah_kerja' => 'Nganjuk, Jombang, Kediri',
                'kepala_nama' => '-',
                'alamat' => 'Jl. Gatot Subroto No.106, Kauman, Kec. Nganjuk, Kabupaten Nganjuk, Jawa Timur',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK Wilayah Bojonegoro',
                'nama' => 'CDK Wilayah Bojonegoro',
                'wilayah_kerja' => 'Bojonegoro, Tuban, Lamongan, Gresik, Lamongan, Sidoarjo, Surabaya',
                'kepala_nama' => '-',
                'alamat' => 'Jl. Hayamwuruk No.9, Karang Pacar, Kec. Bojonegoro, Kabupaten Bojonegoro, Jawa Timur',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK Wilayah Lumajang',
                'nama' => 'CDK Wilayah Lumajang',
                'wilayah_kerja' => 'Lumajang, Probolinggo, Pasuruan',
                'kepala_nama' => '-',
                'alamat' => 'Jl. Langsep No.15, Kepuharjo, Kec. Lumajang, Kabupaten Lumajang, Jawa Timur',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK Wilayah Jember',
                'nama' => 'CDK Wilayah Jember',
                'wilayah_kerja' => 'Jember, Bondowoso',
                'kepala_nama' => '-',
                'alamat' => ' Jl. Trunojoyo Gg. SMAK No.43 – 45, Sawahan Cantian, Kepatihan, Kec. Kaliwates, Kabupaten Jember, Jawa Timur',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK Wilayah Banyuwangi',
                'nama' => 'CDK Wilayah Banyuwangi',
                'wilayah_kerja' => 'Banyuwangi, Situbondo',
                'kepala_nama' => '-',
                'alamat' => 'Jl. Yos Sudarso No.68, Lingkungan Sukowidi, Klatak, Kec. Kalipuro, Kabupaten Banyuwangi, Jawa Timur',
                'is_active' => true,
            ],
            [
                'kode' => 'CDK Wilayah Sumenep',
                'nama' => 'CDK Wilayah Sumenep',
                'wilayah_kerja' => 'Sumenep, Pamekasan, Sampang, Bangkalan',
                'kepala_nama' => '-',
                'alamat' => 'Jl. Dr. Wahidin No. 59, Pajagalan, Kab. Sumenep',
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
