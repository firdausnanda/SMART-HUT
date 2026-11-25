<?php

namespace Modules\Penanaman\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Regencies;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;

class PenanamanAccountController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless($this->user()->hasRole(['admin', 'admin_provinsi']), 403);
    }

    private function authorizeOperator(User $user): void
    {
        abort_unless(
            $user->hasRole('operator_kabupaten') &&
            !$user->hasRole(['admin', 'admin_provinsi']) &&
            $user->regency_id &&
            Regencies::where('id', $user->regency_id)->where('province_id', '35')->exists(),
            404
        );
    }

    public function index()
    {
        $this->authorizeAdmin();
        $operators = User::role('operator_kabupaten')->whereNotNull('regency_id')
            ->orderBy('id')->get(['id', 'regency_id', 'name', 'username', 'email'])
            ->groupBy('regency_id');

        return Inertia::render('Penanaman/Accounts', [
            'regions' => Regencies::where('province_id', '35')->orderBy('name')
                ->get(['id', 'name'])->map(fn ($region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'operators' => $operators->get($region->id, collect())->values(),
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'regency_id' => ['required', Rule::exists('m_regencies', 'id')->where('province_id', '35')],
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $regencyId = $data['regency_id'];
        if (User::role('operator_kabupaten')->where('regency_id', $regencyId)->exists()) {
            return back()->withErrors(['regency_id' => 'Wilayah ini sudah memiliki operator awal.']);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'username' => $data['username'],
            'password' => $data['password'],
            'regency_id' => $regencyId,
            'cdk_id' => null,
        ]);
        $user->assignRole('operator_kabupaten');

        return redirect()->route('penanaman.accounts.index')
            ->with('success', 'Akun operator dibuat dan dapat digunakan untuk masuk.');
    }

    public function resend(User $user)
    {
        $this->authorizeAdmin();
        $this->authorizeOperator($user);
        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (\Throwable $e) {
            Log::warning('Tautan kata sandi operator penanaman gagal dikirim ulang.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $status = null;
        }
        return back()->with(
            $status === Password::RESET_LINK_SENT ? 'success' : 'error',
            $status === Password::RESET_LINK_SENT ? 'Tautan pengaturan kata sandi dikirim.' : 'Email belum terkirim.'
        );
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();
        $this->authorizeOperator($user);
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'regency_id' => ['required', Rule::exists('m_regencies', 'id')->where('province_id', '35')],
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', PasswordRule::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        if (User::role('operator_kabupaten')->where('regency_id', $data['regency_id'])
            ->where('users.id', '!=', $user->id)->exists()) {
            return back()->withErrors(['regency_id' => 'Wilayah ini sudah memiliki operator.']);
        }

        $changes = [
            'regency_id' => $data['regency_id'],
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'username' => $data['username'],
        ];
        if ($request->filled('password')) {
            $changes['password'] = $data['password'];
        }
        $user->update($changes);

        return redirect()->route('penanaman.accounts.index')->with('success', 'Akun operator diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->authorizeAdmin();
        $this->authorizeOperator($user);
        abort_if($user->id === $this->user()->id, 403);

        $user->delete();

        return redirect()->route('penanaman.accounts.index')->with('success', 'Akun operator dihapus.');
    }
}
