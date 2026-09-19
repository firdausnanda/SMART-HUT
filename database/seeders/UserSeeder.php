<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Catatan penting tentang permission:
     * Seeder ini HANYA bertanggung jawab untuk assign ROLE ke user.
     * Permission TIDAK di-assign di sini karena:
     *
     *  1. Role admin/admin_cdk/admin_provinsi → permission sudah diberikan di level ROLE
     *     melalui RoleSeeder (semua permission).
     *  2. Role kacdk → permission view+approve sudah diberikan di level ROLE melalui RoleSeeder.
     *  3. Role kasi/pelaksana → permission dikelola via UI manajemen user
     *     (halaman /users/{id}/edit) secara granular per user, BUKAN di seeder.
     *
     * Logika lama yang melakukan syncPermissions() di seeder ini dihapus karena:
     *  - Nama permission lama (rehab.view, dll.) sudah tidak ada (sudah dimigrasikan ke granular)
     *  - Menyebabkan bug: syncPermissions([]) dengan collection kosong tidak error tapi juga tidak memberi permission
     *  - Desain yang benar: permission per user dikelola via UI, bukan hardcode di seeder
     */
    public function run(): void
    {
        $data = public_path('data/user.csv');

        $file = fopen($data, 'r');

        while (($row = fgetcsv($file)) !== false) {
            $user = User::create([
                'name'     => $row[0],
                'username' => $row[1],
                'email'    => $row[1],
                'password' => bcrypt($row[1]),
            ]);

            // Hanya assign role — permission dikelola via UI atau RoleSeeder
            $user->assignRole($row[2]);
        }

        fclose($file);
    }
}

