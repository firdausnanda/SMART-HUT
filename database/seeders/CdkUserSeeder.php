<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cdk;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CdkUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get operator permissions (same as UserSeeder)
        $pkPermissionNames = [
            'rehab.view',
            'rehab.create',
            'rehab.edit',
            'rehab.delete',
            'perlindungan.view',
            'perlindungan.create',
            'perlindungan.edit',
            'perlindungan.delete',
            'pemberdayaan.view',
            'pemberdayaan.create',
            'pemberdayaan.edit',
            'pemberdayaan.delete',
            'bina-usaha.view',
            'bina-usaha.create',
            'bina-usaha.edit',
            'bina-usaha.delete',
            'penghijauan.view',
            'penghijauan.create',
            'penghijauan.edit',
            'penghijauan.delete',
            'kepegawaian.view',
            'kepegawaian.create',
            'kepegawaian.edit',
            'kepegawaian.delete',
        ];

        $allPermissions = Permission::all();
        $operatorPermissions = $allPermissions->whereIn('name', $pkPermissionNames);

        // 2. Get all CDKs
        $cdks = Cdk::all();

        foreach ($cdks as $cdk) {
            // Extract region name from CDK code/nama (e.g. "CDK Wilayah Pacitan" -> "pacitan")
            $words = explode(' ', trim($cdk->nama));
            $region = strtolower(end($words));

            if (empty($region)) {
                continue;
            }

            // A. Admin CDK
            $adminUser = User::updateOrCreate([
                'email' => "admin.{$region}@smarthut.id",
            ], [
                'name' => "Admin CDK " . ucfirst($region),
                'username' => "admin.{$region}",
                'password' => bcrypt('password123'),
                'cdk_id' => $cdk->id,
            ]);
            $adminUser->assignRole('admin_cdk');
            $adminUser->syncPermissions($allPermissions);

            // B. PK (Penyuluh Kehutanan)
            $pkUser = User::updateOrCreate([
                'email' => "pk.{$region}@smarthut.id",
            ], [
                'name' => "PK CDK " . ucfirst($region),
                'username' => "pk.{$region}",
                'password' => bcrypt('password123'),
                'cdk_id' => $cdk->id,
            ]);
            $pkUser->assignRole('pk');
            $pkUser->syncPermissions($operatorPermissions);

            // C. PEH (Pengendali Ekosistem Hutan)
            $pehUser = User::updateOrCreate([
                'email' => "peh.{$region}@smarthut.id",
            ], [
                'name' => "PEH CDK " . ucfirst($region),
                'username' => "peh.{$region}",
                'password' => bcrypt('password123'),
                'cdk_id' => $cdk->id,
            ]);
            $pehUser->assignRole('peh');
            $pehUser->syncPermissions($operatorPermissions);
        }
    }
}
