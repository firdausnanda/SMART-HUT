<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class PenanamanOperatorSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $role = Role::findByName('operator_kabupaten', 'web');
        $regions = DB::table('m_regencies')
            ->where('province_id', 35)
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($regions->isEmpty()) {
            throw new RuntimeException('Master kabupaten/kota Jawa Timur belum tersedia. Jalankan WilayahSeeder terlebih dahulu.');
        }

        $created = DB::transaction(function () use ($role, $regions): int {
            $created = 0;

            foreach ($regions as $region) {
                if (User::role($role->name)->where('regency_id', $region->id)->exists()) {
                    continue;
                }

                $username = 'operator_' . Str::slug($region->name, '_');
                $email = $username . '@smarthut.jatimprov';
                if (User::where('username', $username)->orWhere('email', $email)->exists()) {
                    throw new RuntimeException("Username atau email cadangan untuk wilayah {$region->id} sudah dipakai akun lain.");
                }

                $user = User::create([
                    'name' => 'Operator ' . $region->name,
                    'username' => $username,
                    'email' => $email,
                    'password' => 'password_' . Str::slug($region->name, '_'),
                    'regency_id' => (string) $region->id,
                    'cdk_id' => null,
                ]);
                $user->assignRole($role);
                $created++;
            }

            return $created;
        });

        $this->command?->info("Akun operator Penanaman baru: {$created} dari {$regions->count()} kabupaten/kota.");
    }
}
