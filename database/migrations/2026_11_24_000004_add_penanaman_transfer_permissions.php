<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        $permissions = collect(['penanaman.export', 'penanaman.import'])
            ->map(fn ($name) => Permission::findOrCreate($name, 'web'));

        foreach (['operator_kabupaten', 'admin', 'admin_provinsi'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])
                ->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', ['penanaman.export', 'penanaman.import'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
