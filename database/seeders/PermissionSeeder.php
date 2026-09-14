<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 20+ Granular Modules mapped exactly to the revised structure
        $modules = [
            // Grub Pembinaan Hutan
            'rehab-lahan' => 'Rehabilitasi Lahan',
            'penghijauan-lingkungan' => 'Penghijauan Lingkungan',
            'rehab-manggrove' => 'Rehabilitasi Mangrove',
            'rhl-teknis' => 'Bangunan Konservasi Tanah dan Air',
            'reboisasi-ps' => 'Reboisasi Area Perhutanan Sosial',

            // Grub Perlindungan dan Jasa Lingkungan
            'kebakaran-hutan' => 'Kebakaran Hutan',
            'pengunjung-wisata' => 'Pengunjung Objek Wisata',

            // Grub Bina Usaha
            'produksi-hutan-negara' => 'Produksi Hutan Negara',
            'produksi-perhutanan-sosial' => 'Produksi Perhutanan Sosial',
            'produksi-hutan-rakyat' => 'Produksi Hutan Rakyat',
            'pbphh' => 'PBPHH',
            'realisasi-pnbp' => 'PNBP',

            // Grub Pemberdayaan Masyarakat
            'skps' => 'Perkembangan SK PS',
            'kups' => 'Perkembangan KUPS',
            'nilai-ekonomi' => 'Nilai Ekonomi (NEKON)',
            'perkembangan-kth' => 'Perkembangan KTH',
            'nilai-transaksi-ekonomi' => 'Nilai Transaksi Ekonomi',

            // Grub Kepegawaian
            'demografi-pegawai' => 'Demografi Pegawai',
            'bezetting-jabatan' => 'Bezetting Jabatan',
            'proyeksi-gaji' => 'Proyeksi Gaji',

            // Grub Users
            'users' => 'Manajemen User',
        ];

        $actions = [
            'view' => 'Melihat',
            'create' => 'Menambah',
            'edit' => 'Mengubah',
            'delete' => 'Menghapus',
            'approve' => 'Menyetujui',
            'export' => 'Export',
            'import' => 'Import',
        ];

        foreach ($modules as $moduleKey => $moduleName) {
            foreach ($actions as $actionKey => $actionName) {
                Permission::updateOrCreate([
                    'name' => "{$moduleKey}.{$actionKey}",
                    'guard_name' => 'web',
                ], [
                    'description' => "{$actionName} {$moduleName}",
                ]);
            }
        }
    }
}
