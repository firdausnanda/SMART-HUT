<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Migration: Fix Role-Level Permissions
 *
 * Masalah: Migration sebelumnya (2026_09_14_025924_migrate_permissions_to_granular)
 * secara tidak sengaja memindahkan permission lama dari level role ke format granular baru
 * menggunakan givePermissionTo() tanpa menghapus role-level permissions yang tidak seharusnya ada.
 *
 * Akibat: Role pelaksana, pk, peh, kasi memiliki 100+ permissions di level role,
 * sehingga semua user dengan role tersebut mendapat akses ke semua modul.
 *
 * Fix:
 *   - pelaksana, pk, peh : hapus semua permission dari level role (dikelola via direct permission per user)
 *   - kasi               : hapus semua permission dari level role (dikelola via direct permission per user)
 *   - kacdk              : TIDAK DIUBAH — tetap punya view+approve di level role
 *   - admin, admin_cdk, admin_provinsi : TIDAK DIUBAH
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesToClear = ['pelaksana', 'pk', 'peh', 'kasi'];

        foreach ($rolesToClear as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->syncPermissions([]);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Rollback tidak dapat me-restore permissions yang dihapus secara otomatis
     * karena state semula adalah kondisi bug (bukan desain yang disengaja).
     *
     * Untuk rollback ke kondisi sebelumnya: restore dari database backup.
     */
    public function down(): void
    {
        // Intentionally left empty.
        // To rollback: restore database backup taken before this migration.
    }
};
