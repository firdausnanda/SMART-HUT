<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserExport;
use App\Exports\UserTemplateExport;
use App\Imports\UserImport;

class UserController extends Controller
{
  private function getGroupedPermissions()
  {
      $allPermissions = \Spatie\Permission\Models\Permission::all();
      
      $map = [
          'Pembinaan Hutan' => ['rehab-lahan', 'penghijauan-lingkungan', 'rehab-manggrove', 'rhl-teknis', 'reboisasi-ps'],
          'Perlindungan & Jasa Lingkungan' => ['kebakaran-hutan', 'pengunjung-wisata'],
          'Bina Usaha' => ['produksi-hutan-negara', 'produksi-perhutanan-sosial', 'produksi-hutan-rakyat', 'pbphh', 'realisasi-pnbp'],
          'Pemberdayaan Masyarakat' => ['skps', 'kups', 'nilai-ekonomi', 'perkembangan-kth', 'nilai-transaksi-ekonomi'],
          'Kepegawaian' => ['demografi-pegawai', 'bezetting-jabatan', 'proyeksi-gaji'],
          'Manajemen User' => ['users']
      ];

      $groupedPermissions = [];
      foreach ($map as $mainGroup => $modules) {
          foreach ($modules as $mod) {
              $perms = $allPermissions->filter(fn($p) => str_starts_with($p->name, $mod . '.'));
              if ($perms->count() > 0) {
                  $groupedPermissions[$mainGroup][$mod] = $perms->values();
              }
          }
      }

      return $groupedPermissions;
  }

  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    $roleFilter = $request->input('role_filter', 'with_role');
    $query = User::with(['roles', 'cdk']);

    if (!auth()->user()->isAdminProvinsi()) {
      $query->where('cdk_id', auth()->user()->cdk_id);
      $query->has('roles');
    } else {
      if ($roleFilter === 'with_role') {
        $query->has('roles');
      } elseif ($roleFilter === 'without_role') {
        $query->doesntHave('roles');
      }
    }

    if ($request->has('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('email', 'like', "%{$search}%")
          ->orWhere('username', 'like', "%{$search}%");
      });
    }

    $users = $query->paginate(10)->withQueryString();

    return Inertia::render('User/Index', [
      'users' => $users,
      'filters' => $request->only(['search', 'role_filter'])
    ]);
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    $roles = $this->getAllowedRoles();
    $cdks = auth()->user()->isAdminProvinsi() ? \App\Models\Cdk::where('is_active', true)->get(['id', 'nama']) : [];
    return Inertia::render('User/Create', [
      'roles' => $roles,
      'cdks' => $cdks,
      'permissions' => $this->getGroupedPermissions()
    ]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $rules = [
      'name' => 'required|string|max:255',
      'username' => 'required|string|max:255|unique:users',
      'email' => 'required|string|lowercase|email|max:255|unique:users',
      'password' => [
        'required',
        'confirmed',
        Password::min(8)          
            ->letters()           
            ->mixedCase()         
            ->numbers()           
            ->symbols()           
            ->uncompromised(),
      ],
      'role' => 'required|exists:roles,name',
      'jabatan' => 'nullable|string|max:100',
      'permissions' => 'nullable|array',
      'permissions.*' => 'exists:permissions,name',
    ];

    if (auth()->user()->isAdminProvinsi()) {
      $rules['cdk_id'] = 'nullable|exists:cdks,id';
    }

    $request->validate($rules);

    $allowedRoles = $this->getAllowedRoles()->pluck('name')->toArray();
    if (!in_array($request->role, $allowedRoles)) {
        return back()->withErrors(['role' => 'Role tidak valid untuk tingkat akses Anda.']);
    }

    $cdkId = auth()->user()->isAdminProvinsi() ? $request->cdk_id : auth()->user()->cdk_id;

    $user = User::create([
      'name' => $request->name,
      'username' => $request->username,
      'email' => $request->email,
      'password' => Hash::make($request->password),
      'cdk_id' => $cdkId,
      'jabatan' => $request->jabatan,
    ]);

    $user->assignRole($request->role);
    if ($request->has('permissions')) {
      $user->syncPermissions($request->permissions);
    }

    return redirect()->route('users.index')->with('success', 'User created successfully.');
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(User $user)
  {
    if (!auth()->user()->isAdminProvinsi() && $user->cdk_id !== auth()->user()->cdk_id) {
      abort(403, 'Unauthorized action.');
    }

    if ($user->getRoleLevel() >= auth()->user()->getRoleLevel() && $user->id !== auth()->id()) {
        abort(403, 'Anda tidak dapat mengedit user dengan role yang setara atau lebih tinggi.');
    }

    $roles = $this->getAllowedRoles();
    $permissions = $this->getGroupedPermissions();

    $cdks = auth()->user()->isAdminProvinsi() ? \App\Models\Cdk::where('is_active', true)->get(['id', 'nama']) : [];

    $user->load(['roles', 'permissions']);

    return Inertia::render('User/Edit', [
      'user' => $user,
      'roles' => $roles,
      'permissions' => $permissions,
      'userPermissions' => $user->permissions->pluck('name'),
      'currentRole' => $user->roles->first()?->name,
      'cdks' => $cdks,
    ]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, User $user)
  {
    if (!auth()->user()->isAdminProvinsi() && $user->cdk_id !== auth()->user()->cdk_id) {
      abort(403, 'Unauthorized action.');
    }

    if ($user->getRoleLevel() >= auth()->user()->getRoleLevel() && $user->id !== auth()->id()) {
        abort(403, 'Anda tidak dapat mengedit user dengan role yang setara atau lebih tinggi.');
    }

    $rules = [
      'name' => 'required|string|max:255',
      'username' => 'required|string|max:255|unique:users,username,' . $user->id,
      'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
      'role' => 'required|exists:roles,name',
      'jabatan' => 'nullable|string|max:100',
      'permissions' => 'nullable|array',
      'permissions.*' => 'exists:permissions,name',
    ];

    if (auth()->user()->isAdminProvinsi()) {
      $rules['cdk_id'] = 'nullable|exists:cdks,id';
    }

    if ($request->filled('password')) {
      $rules['password'] = ['confirmed', Password::min(8)          
            ->letters()           
            ->mixedCase()         
            ->numbers()           
            ->symbols()           
            ->uncompromised()];
    }

    $validated = $request->validate($rules);

    if ($user->id !== auth()->id()) {
        $allowedRoles = $this->getAllowedRoles()->pluck('name')->toArray();
        if (!in_array($request->role, $allowedRoles)) {
            return back()->withErrors(['role' => 'Role tidak valid untuk tingkat akses Anda.']);
        }
    } else {
        if ($request->role !== $user->roles->first()?->name) {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah role Anda sendiri.']);
        }
    }

    $userData = \Illuminate\Support\Arr::except($validated, ['role', 'permissions', 'password', 'cdk_id']);

    if ($request->filled('password')) {
      $userData['password'] = Hash::make($request->password);
    }

    if (auth()->user()->isAdminProvinsi()) {
      $userData['cdk_id'] = $request->cdk_id;
    }

    $userData['jabatan'] = $request->jabatan;

    $user->update($userData);

    $user->syncRoles([$request->role]);

    // Sync direct permissions
    if ($request->has('permissions')) {
      $user->syncPermissions($request->permissions);
    }

    return redirect()->route('users.index')->with('success', 'User updated successfully.');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(User $user)
  {
    if (!auth()->user()->isAdminProvinsi() && $user->cdk_id !== auth()->user()->cdk_id) {
      abort(403, 'Unauthorized action.');
    }

    if ($user->id === auth()->id()) {
      return back()->with('error', 'You cannot delete your own account.');
    }

    if ($user->getRoleLevel() >= auth()->user()->getRoleLevel()) {
      return back()->with('error', 'Anda tidak dapat menghapus user dengan role yang setara atau lebih tinggi.');
    }

    $user->delete();

    return redirect()->route('users.index')->with('success', 'User deleted successfully.');
  }

  public function export(Request $request)
  {
    $this->authorize('users.view');
    return Excel::download(new UserExport($request), 'data_users.xlsx');
  }

  public function exportTemplate()
  {
    $this->authorize('users.view');
    return Excel::download(new UserTemplateExport(), 'template_import_users.xlsx');
  }

  public function import(Request $request)
  {
    $this->authorize('users.create');
    $request->validate([
      'file' => 'required|mimes:xlsx,xls,csv|max:10240', // Max 10MB
    ]);

    try {
      Excel::import(new UserImport, $request->file('file'));
      return redirect()->back()->with('success', 'Data User berhasil diimport.');
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
      $failures = $e->failures();
      $messages = [];
      foreach ($failures as $failure) {
        $messages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
      }
      return redirect()->back()->with('error', 'Gagal mengimport data: <br>' . implode('<br>', $messages));
    } catch (\Exception $e) {
      return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
  }

  private function getAllowedRoles()
  {
    $currentUserLevel = auth()->user()->getRoleLevel();
    
    return Role::all()->filter(function ($role) use ($currentUserLevel) {
      $roleLevel = match ($role->name) {
        'admin' => 6,
        'admin_provinsi' => 5,
        'admin_cdk' => 4,
        'kacdk' => 3,
        'kasi' => 2,
        default => 1,
      };
      
      return $roleLevel < $currentUserLevel;
    })->values();
  }
}
