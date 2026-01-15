<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $data = public_path('data/user.csv');

        $file = fopen($data, 'r');

        while (($row = fgetcsv($file)) !== false) {

            $user = User::create([
                'name' => $row[0],
                'username' => $row[1],
                'email' => $row[1],
                'password' => bcrypt($row[1]),
            ]);

            $user->assignRole($row[2]);
        }

        fclose($file);


        // $admin = User::create([
        //     'name' => 'Admin',
        //     'username' => 'admin',
        //     'email' => 'admin@admin.com',
        //     'password' => bcrypt('password'),
        // ]);

        // $admin->assignRole('admin');

        // $pk = User::create([
        //     'name' => 'pk',
        //     'username' => 'pk',
        //     'email' => 'pk@pk.com',
        //     'password' => bcrypt('password'),
        // ]);

        // $pk->assignRole('pk');

        // $kasi = User::create([
        //     'name' => 'kasi',
        //     'username' => 'kasi',
        //     'email' => 'kasi@kasi.com',
        //     'password' => bcrypt('password'),
        // ]);

        // $kasi->assignRole('kasi');

        // $cdk = User::create([
        //     'name' => 'cdk',
        //     'username' => 'cdk',
        //     'email' => 'cdk@cdk.com',
        //     'password' => bcrypt('password'),
        // ]);

        // $cdk->assignRole('kacdk');
    }
}
