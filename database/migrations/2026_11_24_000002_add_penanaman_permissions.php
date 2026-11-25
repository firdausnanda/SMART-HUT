<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    private const ACTIONS = ['view', 'create', 'edit', 'submit', 'return', 'target'];

    public function up(): void
    {
        foreach (self::ACTIONS as $action) {
            Permission::findOrCreate("penanaman.$action", 'web');
        }

        $operator = Role::firstOrCreate(
            ['name' => 'operator_kabupaten', 'guard_name' => 'web'],
            ['description' => 'Operator Kabupaten/Kota']
        );
        $operator->syncPermissions(Permission::whereIn('name', [
            'penanaman.view', 'penanaman.create', 'penanaman.edit',
            'penanaman.submit', 'penanaman.target',
        ])->get());

        foreach (['admin', 'admin_provinsi'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'])->givePermissionTo(
                Permission::whereIn('name', array_map(fn ($action) => "penanaman.$action", self::ACTIONS))->get()
            );
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::where('name', 'operator_kabupaten')->delete();
        Permission::whereIn('name', array_map(fn ($action) => "penanaman.$action", self::ACTIONS))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
