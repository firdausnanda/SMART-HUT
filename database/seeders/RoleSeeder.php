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
            [
                'name' => 'operator_kabupaten',
                'description' => 'Operator Kabupaten/Kota',
            ],
        ];

        $allPermissions = \Spatie\Permission\Models\Permission::all();

        // Permission view + approve untuk semua modul (dipakai oleh kacdk di level role)
        $viewApprovePermissions = $allPermissions->filter(
            fn($p) => !str_starts_with($p->name, 'penanaman.') &&
                (str_ends_with($p->name, '.view') || str_ends_with($p->name, '.approve'))
        );

        foreach ($roles as $role) {
            $createdRole = Role::firstOrCreate([
                'name' => $role['name'],
                'guard_name' => 'web',
            ], [
                'description' => $role['description'],
            ]);

            if (in_array($createdRole->name, ['admin', 'admin_provinsi'])) {
                // Super-admin roles: semua permission di level role
                $createdRole->syncPermissions($allPermissions);
            } elseif ($createdRole->name === 'admin_cdk') {
                $createdRole->syncPermissions($allPermissions->reject(fn ($p) => str_starts_with($p->name, 'penanaman.')));
            } elseif ($createdRole->name === 'operator_kabupaten') {
                $createdRole->syncPermissions($allPermissions->whereIn('name', [
                    'penanaman.view', 'penanaman.create', 'penanaman.edit', 'penanaman.delete',
                      'penanaman.submit', 'penanaman.target',
                      'penanaman.export', 'penanaman.import',
                ]));
            } elseif ($createdRole->name === 'kacdk') {
                // Kepala CDK: hanya view + approve di level role
                // Permission granular lain dikelola via direct permission per user
                $createdRole->syncPermissions($viewApprovePermissions);
            } else {
                // kasi, pelaksana:
                // TIDAK punya permission di level role.
                // Semua akses dikelola via direct permission per user melalui UI manajemen user.
                $createdRole->syncPermissions([]);
            }
        }
    }
}
