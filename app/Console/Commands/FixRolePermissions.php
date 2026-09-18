<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class FixRolePermissions extends Command
{
    /**
     * php artisan permission:fix-roles
     * php artisan permission:fix-roles --dry-run   (preview saja)
     * php artisan permission:fix-roles --yes        (skip konfirmasi, untuk server/CI)
     */
    protected $signature = 'permission:fix-roles
                            {--dry-run : Preview perubahan tanpa mengeksekusi}
                            {--yes    : Skip konfirmasi (untuk CI/deploy server)}';

    protected $description = 'Fix role-level permissions: hapus permission dari role pelaksana/pk/peh/kasi, pertahankan kacdk';

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>====================================================</>');
        $this->line('<fg=cyan;options=bold>  Permission Fix: Role-Level Permissions Cleanup   </>');
        $this->line('<fg=cyan;options=bold>====================================================</>');
        $this->newLine();

        // Role yang akan di-clear semua permission-nya dari level role
        // Penjelasan:
        //   pelaksana, pk, peh → tidak punya permission di level role, dikelola via direct permission per user
        //   kasi               → per direct permission (keputusan: 2026-09-18)
        //   kacdk              → TETAP, permission view+approve di level role (dipertahankan)
        $rolesToClear = ['pelaksana', 'pk', 'peh', 'kasi'];

        // --- SNAPSHOT SEBELUM ---
        $this->line('<options=bold>📊 State sebelum fix:</>');
        $this->newLine();
        $allRoles = Role::with('permissions')->orderBy('id')->get();
        $table = $allRoles->map(fn($r) => [
            $r->name,
            $r->permissions->count(),
            in_array($r->name, $rolesToClear) ? '<fg=red>AKAN DI-CLEAR</>' : '<fg=green>TIDAK BERUBAH</>',
        ])->toArray();
        $this->table(['Role', '# Permissions', 'Action'], $table);
        $this->newLine();

        // --- DRY RUN ---
        if ($this->option('dry-run')) {
            $this->warn('⚡ DRY RUN aktif — tidak ada perubahan yang dilakukan.');
            $this->line('   Jalankan tanpa --dry-run untuk mengeksekusi.');
            $this->newLine();
            return self::SUCCESS;
        }

        // --- KONFIRMASI ---
        if (!$this->option('yes')) {
            $this->warn('⚠️  Perubahan ini akan menghapus permission dari role-level untuk:');
            foreach ($rolesToClear as $r) {
                $this->line("   • {$r}");
            }
            $this->newLine();
            if (!$this->confirm('Lanjutkan eksekusi?', false)) {
                $this->info('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        // --- EKSEKUSI ---
        $this->newLine();
        $this->line('<options=bold>⚙️  Mengeksekusi...</>');
        $this->newLine();

        foreach ($rolesToClear as $roleName) {
            $role = Role::with('permissions')->where('name', $roleName)->first();
            if (!$role) {
                $this->warn("  ⚠  Role [{$roleName}] tidak ditemukan di database, dilewati.");
                continue;
            }

            $beforeCount = $role->permissions->count();
            $role->syncPermissions([]);

            $this->line("  <fg=green>✔</> [{$roleName}] → {$beforeCount} permissions dihapus dari role-level");
        }

        // Flush Spatie permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->line("  <fg=green>✔</> Permission cache di-reset");

        // --- SNAPSHOT SESUDAH ---
        $this->newLine();
        $this->line('<options=bold>📊 State sesudah fix:</>');
        $this->newLine();
        $allRolesAfter = Role::with('permissions')->orderBy('id')->get();
        $tableAfter = $allRolesAfter->map(fn($r) => [
            $r->name,
            $r->permissions->count(),
        ])->toArray();
        $this->table(['Role', '# Permissions'], $tableAfter);
        $this->newLine();

        $this->line('<fg=green;options=bold>✅ Selesai! Role permissions berhasil diperbaiki.</>');
        $this->newLine();
        $this->line('<fg=yellow>LANGKAH SELANJUTNYA:</>');
        $this->line('   1. Buka halaman /users di browser dan verifikasi akses user pelaksana');
        $this->line('   2. Assign direct permission ke user yang membutuhkan akses via halaman Edit User');
        $this->newLine();

        return self::SUCCESS;
    }
}
