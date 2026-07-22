<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
  public function __construct()
  {
    $this->middleware('permission:users.view')->only(['index', 'show']);
    $this->middleware('permission:users.create')->only(['create', 'store']);
    $this->middleware('permission:users.edit')->only(['edit', 'update']);
    $this->middleware('permission:users.delete')->only(['destroy']);
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
    }

    if ($roleFilter === 'with_role') {
      $query->has('roles');
    } elseif ($roleFilter === 'without_role') {
      $query->doesntHave('roles');
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
    $roles = Role::all();
    $cdks = auth()->user()->isAdminProvinsi() ? \App\Models\Cdk::where('is_active', true)->get(['id', 'nama']) : [];
    return Inertia::render('User/Create', [
      'roles' => $roles,
      'cdks' => $cdks
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
    ];

    if (auth()->user()->isAdminProvinsi()) {
      $rules['cdk_id'] = 'nullable|exists:cdks,id';
    }

    $request->validate($rules);

    $cdkId = auth()->user()->isAdminProvinsi() ? $request->cdk_id : auth()->user()->cdk_id;

    $user = User::create([
      'name' => $request->name,
      'username' => $request->username,
      'email' => $request->email,
      'password' => Hash::make($request->password),
      'cdk_id' => $cdkId,
    ]);

    $user->assignRole($request->role);

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

    $roles = Role::all();
    $permissions = \Spatie\Permission\Models\Permission::all()->groupBy(function ($data) {
      return explode('.', $data->name)[0];
    });

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

    $rules = [
      'name' => 'required|string|max:255',
      'username' => 'required|string|max:255|unique:users,username,' . $user->id,
      'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
      'role' => 'required|exists:roles,name',
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

    $userData = \Illuminate\Support\Arr::except($validated, ['role', 'permissions', 'password', 'cdk_id']);

    if ($request->filled('password')) {
      $userData['password'] = Hash::make($request->password);
    }

    if (auth()->user()->isAdminProvinsi()) {
      $userData['cdk_id'] = $request->cdk_id;
    }

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

    if ($user->hasRole('admin') && !auth()->user()->hasRole('admin')) {
      return back()->with('error', 'User dengan role admin tidak dapat dihapus oleh role lain.');
    }

    $user->delete();

    return redirect()->route('users.index')->with('success', 'User deleted successfully.');
  }
}
