<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Cdk;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class UserImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function rules(): array
    {
        return [
            'nama_lengkap' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => 'nullable|string|min:8',
            'role' => 'nullable|exists:roles,name',
            'nama_cdk' => 'nullable|string|exists:cdks,nama',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama Lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'email.email' => 'Format email tidak valid.',
            'role.exists' => 'Role tidak ditemukan.',
            'nama_cdk.exists' => 'Nama CDK tidak ditemukan di sistem.',
        ];
    }

    public function model(array $row)
    {
        $username = trim($row['username'] ?? '');
        $email = trim($row['email'] ?? '');
        
        if (empty($username) || empty($email)) {
            return null;
        }

        // Check if user already exists (just to be safe, although validation covers it)
        if (User::where('email', $email)->orWhere('username', $username)->exists()) {
            return null;
        }

        $isAdminProvinsi = auth()->user()->isAdminProvinsi();
        
        // Default to current user's CDK ID
        $cdkId = auth()->user()->cdk_id;

        // If admin and they provided a CDK Name, look it up
        if ($isAdminProvinsi && !empty($row['nama_cdk'])) {
            $cdkName = trim($row['nama_cdk']);
            $cdk = Cdk::where('nama', $cdkName)->first();
            if ($cdk) {
                $cdkId = $cdk->id;
            }
        }

        $password = !empty($row['password']) ? trim($row['password']) : 'Password123!';

        $user = new User([
            'name' => trim($row['nama_lengkap']),
            'username' => $username,
            'email' => strtolower($email),
            'password' => Hash::make($password),
            'cdk_id' => $cdkId,
        ]);

        $user->save();

        $roleName = !empty($row['role']) ? trim($row['role']) : 'user';
        if (Role::where('name', $roleName)->exists()) {
            $user->assignRole($roleName);
        }

        return $user;
    }
}
