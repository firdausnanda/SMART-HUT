<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Administrator',
            ],
            [
                'name' => 'pelaksana',
                'description' => 'Pelaksana',
            ],
            [
                'name' => 'pk',
                'description' => 'Penyuluh Kehutanan',
            ],
            [
                'name' => 'peh',
                'description' => 'Pengendali Ekosistem Hutan',
            ],
            [
                'name' => 'kasi',
                'description' => 'Kepala Seksi',
            ],
            [
                'name' => 'kacdk',
                'description' => 'Kepala CDK',
            ],
            [
                'name' => 'admin_cdk',
                'description' => 'Admin CDK',
            ],
            [
                'name' => 'admin_provinsi',
                'description' => 'Admin Provinsi',
            ],
        ];

        $allPermissions = \Spatie\Permission\Models\Permission::all();

        foreach ($roles as $role) {
            $createdRole = Role::firstOrCreate([
                'name' => $role['name'],
                'guard_name' => 'web',
            ], [
                'description' => $role['description'],
            ]);

            if (in_array($createdRole->name, ['admin', 'admin_provinsi', 'admin_cdk'])) {
                $createdRole->syncPermissions($allPermissions);
            }
        }
    }
}
