<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Map old module groups to new granular modules
        $mapping = [
            'rehab' => ['rehab-lahan', 'rehab-manggrove', 'rhl-teknis', 'reboisasi-ps'],
            'penghijauan' => ['penghijauan-lingkungan'],
            'perlindungan' => ['kebakaran-hutan'],
            'bina-usaha' => ['produksi-hutan-negara', 'produksi-perhutanan-sosial', 'produksi-hutan-rakyat', 'pbphh', 'realisasi-pnbp', 'pengunjung-wisata'],
            'pemberdayaan' => ['skps', 'kups', 'nilai-ekonomi', 'perkembangan-kth', 'nilai-transaksi-ekonomi'],
            'kepegawaian' => ['demografi-pegawai', 'bezetting-jabatan', 'proyeksi-gaji'],
            'master' => ['provinces', 'regencies', 'districts', 'villages', 'bangunan-kta', 'sumber-dana', 'commodities', 'bukan-kayu', 'kayu', 'jenis-produksi', 'pengelola-wisata', 'pengelola-ps', 'skema-perhutanan-sosial'],
            'users' => ['users'],
        ];

        // Ensure mapping aligns exactly with the user's revised grouping from earlier.
        // Wait, the user said:
        // Pembinaan: Rehab Lahan, Penghijauan, Rehab Mangrove, Bangunan KTA (rhl-teknis), Reboisasi PS
        // Perlindungan: Kebakaran Hutan, Pengunjung Wisata
        // Bina Usaha: Produksi Hutan Negara, Hutan Sosial, Hutan Rakyat, PBPHH, PNBP

        // Let's adjust mapping based on old names!
        // The old groups were from `web.php` and `PermissionSeeder`.
        // If a user had `rehab.view`, they should now get: rehab-lahan, rehab-manggrove, rhl-teknis, reboisasi-ps, penghijauan-lingkungan (just in case they didn't have it separately)
        // Actually, let's map based on what they *used* to have, to their new equivalents.
        
        $migrationMapping = [
            'rehab' => ['rehab-lahan', 'rehab-manggrove', 'rhl-teknis', 'reboisasi-ps'],
            'penghijauan' => ['penghijauan-lingkungan'],
            'perlindungan' => ['kebakaran-hutan'], // and pengunjung-wisata was under bina-usaha in web.php
            'bina-usaha' => ['pengunjung-wisata', 'produksi-hutan-negara', 'produksi-perhutanan-sosial', 'produksi-hutan-rakyat', 'pbphh', 'realisasi-pnbp'],
            'pemberdayaan' => ['skps', 'kups', 'nilai-ekonomi', 'perkembangan-kth', 'nilai-transaksi-ekonomi'],
            'kepegawaian' => ['demografi-pegawai', 'bezetting-jabatan', 'proyeksi-gaji'],
            'master' => ['provinces', 'regencies', 'districts', 'villages', 'bangunan-kta', 'sumber-dana', 'commodities', 'bukan-kayu', 'kayu', 'jenis-produksi', 'pengelola-wisata', 'pengelola-ps', 'skema-perhutanan-sosial'],
            'users' => ['users'],
        ];

        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export', 'import'];

        // Create new permissions
        foreach ($migrationMapping as $old => $news) {
            foreach ($news as $newModule) {
                foreach ($actions as $action) {
                    Permission::firstOrCreate(['name' => "{$newModule}.{$action}", 'guard_name' => 'web']);
                }
            }
        }

        // Migrate Roles
        $roles = Role::with('permissions')->get();
        foreach ($roles as $role) {
            $newPermNames = [];
            foreach ($role->permissions as $perm) {
                $parts = explode('.', $perm->name);
                if (count($parts) === 2) {
                    $oldModule = $parts[0];
                    $action = $parts[1];
                    if (isset($migrationMapping[$oldModule])) {
                        foreach ($migrationMapping[$oldModule] as $newModule) {
                            $newPermNames[] = "{$newModule}.{$action}";
                        }
                    }
                }
                $newPermNames[] = $perm->name; // Temporarily keep the old one
            }
            if (!empty($newPermNames)) {
                $role->givePermissionTo(array_unique($newPermNames));
            }
        }

        // Migrate Users
        $users = \App\Models\User::with('permissions')->get();
        foreach ($users as $user) {
            $newPermNames = [];
            foreach ($user->permissions as $perm) {
                $parts = explode('.', $perm->name);
                if (count($parts) === 2) {
                    $oldModule = $parts[0];
                    $action = $parts[1];
                    if (isset($migrationMapping[$oldModule])) {
                        foreach ($migrationMapping[$oldModule] as $newModule) {
                            $newPermNames[] = "{$newModule}.{$action}";
                        }
                    }
                }
            }
            if (!empty($newPermNames)) {
                $user->givePermissionTo(array_unique($newPermNames));
            }
        }

        // Delete old permissions except 'users'
        foreach (array_keys($migrationMapping) as $oldModule) {
            if ($oldModule !== 'users') {
                foreach ($actions as $action) {
                    $p = Permission::where('name', "{$oldModule}.{$action}")->first();
                    if ($p) {
                        $p->delete();
                    }
                }
            }
        }
        
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 
    }
};
