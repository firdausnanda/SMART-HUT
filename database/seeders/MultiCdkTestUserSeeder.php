<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MultiCdkTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = Permission::all();

        // Create Test Users for Multi-CDK
        $provinsiUser = User::updateOrCreate([
            'email' => 'admin.provinsi@smarthut.id',
        ], [
            'name' => 'Admin Provinsi Jatim',
            'username' => 'admin.provinsi',
            'password' => bcrypt('password123'),
        ]);
        $provinsiUser->assignRole('admin_provinsi');
        $provinsiUser->syncPermissions($permissions);

        // $cdkUser = User::updateOrCreate([
        //     'email' => 'admin.cdk.trg@smarthut.id',
        // ], [
        //     'name' => 'Admin CDK Trenggalek',
        //     'username' => 'admin.cdk.trg',
        //     'password' => bcrypt('password123'),
        // ]);
        // $cdkUser->assignRole('admin_cdk');
        // $cdkUser->syncPermissions($permissions);
    }
}
