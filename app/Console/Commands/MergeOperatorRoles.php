<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MergeOperatorRoles extends Command
{
    /**
     * php artisan roles:merge-operator
     * php artisan roles:merge-operator --dry-run   (preview saja, tidak ada perubahan)
     * php artisan roles:merge-operator --yes        (skip konfirmasi, untuk deploy server)
     */
    protected $signature = 'roles:merge-operator
                            {--dry-run : Preview perubahan tanpa mengeksekusi}
                            {--yes    : Skip konfirmasi (untuk CI/deploy server)}';

    protected $description = 'Merge role pk & peh ke role pelaksana, simpan label jabatan di kolom users.jabatan';

    // Mapping role lama → jabatan yang akan disimpan ke kolom users.jabatan
    private array $roleJabatanMap = [
        'pk'        => 'Penyuluh Kehutanan',
        'peh'       => 'Pengendali Ekosistem Hutan',
        'pelaksana' => 'Pelaksana', // untuk user pelaksana yang belum punya jabatan
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>============================================================</>');
        $this->line('<fg=cyan;options=bold>   Merge Role PK & PEH → Pelaksana + Assign Jabatan       </>');
        $this->line('<fg=cyan;options=bold>============================================================</>');
        $this->newLine();

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('⚡ DRY RUN aktif — tidak ada perubahan yang akan dilakukan.');
            $this->newLine();
        }

        // ── SNAPSHOT SEBELUM ────────────────────────────────────────────
        $this->line('<options=bold>📊 Kondisi role sebelum merge:</options>');
        $allRoles = Role::withCount('users')->orderBy('id')->get();
        $this->table(
            ['Role', 'Description', '# Users'],
            $allRoles->map(fn($r) => [$r->name, $r->description ?? '-', $r->users_count])->toArray()
        );
        $this->newLine();

        // ── HITUNG DAMPAK ───────────────────────────────────────────────
        $pkUsers  = User::role('pk')->get();
        $pehUsers = User::role('peh')->get();
        $pelaksanaWithoutJabatan = User::role('pelaksana')->whereNull('jabatan')->get();

        $this->line('<options=bold>📋 Rencana perubahan:</options>');
        $this->line("  • <fg=yellow>{$pkUsers->count()}</> user role [pk] → role <fg=green>pelaksana</>, jabatan = <fg=green>Penyuluh Kehutanan</>");
        $this->line("  • <fg=yellow>{$pehUsers->count()}</> user role [peh] → role <fg=green>pelaksana</>, jabatan = <fg=green>Pengendali Ekosistem Hutan</>");
        $this->line("  • <fg=yellow>{$pelaksanaWithoutJabatan->count()}</> user [pelaksana] tanpa jabatan → jabatan = <fg=green>Pelaksana</>");
        $this->line("  • Role [pk] dan [peh] akan <fg=red>dihapus</> dari tabel roles");
        $this->newLine();

        if ($isDryRun) {
            $this->info('✅ Dry run selesai. Jalankan tanpa --dry-run untuk mengeksekusi.');
            return self::SUCCESS;
        }

        // ── KONFIRMASI ──────────────────────────────────────────────────
        if (!$this->option('yes')) {
            $this->warn('⚠️  Operasi ini akan mengubah data user dan menghapus role pk & peh.');
            if (!$this->confirm('Lanjutkan eksekusi?', false)) {
                $this->info('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->line('<options=bold>⚙️  Mengeksekusi...</options>');
        $this->newLine();

        // ── MIGRATE USER PK ─────────────────────────────────────────────
        $migratedPk = 0;
        foreach ($pkUsers as $user) {
            $user->jabatan = 'Penyuluh Kehutanan';
            $user->save();
            $user->syncRoles(['pelaksana']);
            $migratedPk++;
        }
        $this->line("  <fg=green>✔</> [{$migratedPk}] user pk → pelaksana + jabatan 'Penyuluh Kehutanan'");

        // ── MIGRATE USER PEH ─────────────────────────────────────────────
        $migratedPeh = 0;
        foreach ($pehUsers as $user) {
            $user->jabatan = 'Pengendali Ekosistem Hutan';
            $user->save();
            $user->syncRoles(['pelaksana']);
            $migratedPeh++;
        }
        $this->line("  <fg=green>✔</> [{$migratedPeh}] user peh → pelaksana + jabatan 'Pengendali Ekosistem Hutan'");

        // ── SET JABATAN UNTUK PELAKSANA YANG BELUM PUNYA JABATAN ─────────
        $filledJabatan = 0;
        foreach ($pelaksanaWithoutJabatan as $user) {
            $user->jabatan = 'Pelaksana';
            $user->save();
            $filledJabatan++;
        }
        $this->line("  <fg=green>✔</> [{$filledJabatan}] user pelaksana → jabatan 'Pelaksana' diisi");

        // ── HAPUS ROLE PK & PEH ──────────────────────────────────────────
        $deletedRoles = [];
        foreach (['pk', 'peh'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->delete();
                $deletedRoles[] = $roleName;
                $this->line("  <fg=green>✔</> Role [{$roleName}] dihapus");
            } else {
                $this->warn("  ⚠  Role [{$roleName}] tidak ditemukan, mungkin sudah dihapus sebelumnya");
            }
        }

        // ── FLUSH PERMISSION CACHE ───────────────────────────────────────
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->line("  <fg=green>✔</> Permission cache di-reset");

        // ── SNAPSHOT SESUDAH ─────────────────────────────────────────────
        $this->newLine();
        $this->line('<options=bold>📊 Kondisi role sesudah merge:</options>');
        $allRolesAfter = Role::withCount('users')->orderBy('id')->get();
        $this->table(
            ['Role', 'Description', '# Users'],
            $allRolesAfter->map(fn($r) => [$r->name, $r->description ?? '-', $r->users_count])->toArray()
        );

        $this->newLine();
        $this->line('<fg=green;options=bold>✅ Selesai! Merge role berhasil.</>');
        $this->newLine();
        $this->line('<fg=yellow>LANGKAH SELANJUTNYA:</>');
        $this->line('   1. Jalankan: php artisan config:cache && php artisan optimize');
        $this->line('   2. Verifikasi login user ex-PK/PEH di browser');
        $this->line('   3. Pastikan sidebar menampilkan jabatan yang benar');
        $this->newLine();

        return self::SUCCESS;
    }
}
