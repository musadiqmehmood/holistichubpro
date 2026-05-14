<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Events\RoleChanged;
use App\Events\PermissionChanged;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use League\Csv\Reader;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['roles.permissions', 'permissions', 'branch']);

        if (!$request->user()->hasRole(config('rbac.super_admin_role'))) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $users = $query->paginate(15);

        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'branch_id' => $user->branch_id,
                'branch' => $user->branch,
                'email_verified_at' => $user->email_verified_at,
                'last_login_at' => $user->last_login_at,
                'last_login_ip' => $user->last_login_ip,
                'created_at' => $user->created_at,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                        'permissions' => $role->permissions->pluck('name'),
                    ];
                }),
                'direct_permissions' => $user->permissions->map(function ($perm) {
                    return [
                        'id' => $perm->id,
                        'name' => $perm->name,
                    ];
                }),
                'all_permissions' => $user->getAllPermissions()->pluck('name'),
            ];
        });

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users',
            'password'    => ['required', Password::defaults()],
            'phone'       => 'nullable|string|max:20',
            'branch_id'   => 'required|exists:branches,id',
            'roles'       => 'nullable|array',
            'roles.*'     => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $user = DB::transaction(function () use ($validated, $request) {
            $emailVerifiedAt = $request->user()->hasRole(config('rbac.super_admin_role')) ? now() : null;

            $newUser = User::create([
                'name'                => $validated['name'],
                'email'               => $validated['email'],
                'password'            => Hash::make($validated['password']),
                'phone'               => $validated['phone'] ?? null,
                'branch_id'           => $validated['branch_id'],
                'password_changed_at' => now(),
                'email_verified_at'   => $emailVerifiedAt,
            ]);

            if (!empty($validated['roles'])) {
                $this->assignRolesWithBranch($newUser, $validated['roles']);
            }

            if (!empty($validated['permissions'])) {
                $permissions = Permission::whereIn('id', $validated['permissions'])->get();
                $newUser->givePermissionTo($permissions);
            }

            AuditLogService::log('created', User::class, $newUser->id, null, $newUser->toArray());

            return $newUser;
        });

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(
            $user->load(['roles.permissions', 'permissions', 'branch']),
            201
        );
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load(['roles.permissions', 'permissions', 'branch']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'branch_id' => $user->branch_id,
            'branch' => $user->branch,
            'email_verified_at' => $user->email_verified_at,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $role->permissions->map(function ($perm) {
                        return [
                            'id' => $perm->id,
                            'name' => $perm->name,
                        ];
                    }),
                ];
            }),
            'direct_permissions' => $user->permissions->map(function ($perm) {
                return [
                    'id' => $perm->id,
                    'name' => $perm->name,
                ];
            }),
            'all_permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'email'       => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'       => 'nullable|string|max:20',
            'branch_id'   => 'sometimes|nullable|exists:branches,id',
            'roles'       => 'sometimes|array',
            'roles.*'     => 'exists:roles,id',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,id',
            'password'    => ['sometimes', Password::defaults()],
        ]);

        $oldValues = $user->toArray();

        DB::transaction(function () use ($validated, $user, $request) {
            $updateData = collect($validated)->only(['name', 'email', 'phone'])->toArray();

            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
                $updateData['password_changed_at'] = now();
            }

            if (array_key_exists('branch_id', $validated)) {
                $this->handleBranchChange($user, $validated['branch_id']);
                $updateData['branch_id'] = $validated['branch_id'];
            }

            $user->update($updateData);

            if (isset($validated['roles'])) {
                $this->syncUserRoles($user, $validated['roles'], $request);
            }

            if (isset($validated['permissions'])) {
                $this->syncUserPermissions($user, $validated['permissions'], $request);
            }
        });

        AuditLogService::log('updated', User::class, $user->id, $oldValues, $user->fresh()->toArray());

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(
            $user->refresh()->load(['roles.permissions', 'permissions', 'branch'])
        );
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        if ($user->hasRole(config('rbac.super_admin_role'))) {
            return response()->json([
                'message' => 'Cannot delete super admin user',
            ], 403);
        }

        $oldData = $user->toArray();
        $userId = $user->id;

        $user->delete();

        AuditLogService::log('deleted', User::class, $userId, $oldData, null);

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function auditLogs(User $user)
    {
        $this->authorize('view', $user);

        $logs = \App\Models\AuditLog::where('entity_type', User::class)
            ->where('entity_id', $user->id)
            ->with('performer')
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }

    private function handleBranchChange(User $user, ?int $newBranchId): void
    {
        if ($user->branch_id == $newBranchId) {
            return;
        }

        if (is_null($newBranchId)) {
            DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', User::class)
                ->delete();
        } else {
            DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', User::class)
                ->update(['branch_id' => $newBranchId]);
        }
    }

    private function assignRolesWithBranch(User $user, array $roleIds): void
    {
        if (is_null($user->branch_id)) {
            \Log::warning('Attempted to assign role to user without branch_id', [
                'user_id' => $user->id,
                'roles'   => $roleIds,
            ]);
            return;
        }

        $roles = Role::whereIn('id', $roleIds)->get();

        $user->assignRole($roles);

        foreach ($roles as $role) {
            DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->where('model_id', $user->id)
                ->where('model_type', User::class)
                ->update(['branch_id' => $user->branch_id]);
        }
    }

    private function syncUserRoles(User $user, array $newRoleIds, Request $request): void
    {
        $oldRoles = $user->roles->pluck('id')->toArray();
        $detached = array_diff($oldRoles, $newRoleIds);
        $attached = array_diff($newRoleIds, $oldRoles);

        $newRoles = Role::whereIn('id', $newRoleIds)->get();

        $user->syncRoles($newRoles);

        if (!is_null($user->branch_id)) {
            DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', User::class)
                ->update(['branch_id' => $user->branch_id]);
        }

        foreach ($attached as $roleId) {
            event(new RoleChanged($user, 'attached', $roleId, null, $request->ip(), $request->userAgent()));
        }
        foreach ($detached as $roleId) {
            event(new RoleChanged($user, 'detached', $roleId, null, $request->ip(), $request->userAgent()));
        }
    }

    public function import(Request $request)
    {
        $this->authorize('create', User::class);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $csv = Reader::createFromPath($request->file('file')->getRealPath(), 'r');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        $imported = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($records as $index => $record) {
                try {
                    if (empty($record['name']) || empty($record['email']) || empty($record['password'])) {
                        throw new \Exception("Missing required field (name, email, or password)");
                    }

                    $branchId = !empty($record['branch_id']) ? (int)$record['branch_id'] : null;
                    if ($branchId && !\App\Models\Branch::where('id', $branchId)->exists()) {
                        throw new \Exception("Branch ID {$branchId} does not exist");
                    }

                    $user = User::create([
                        'name' => $record['name'],
                        'email' => $record['email'],
                        'password' => Hash::make($record['password']),
                        'branch_id' => $branchId,
                        'email_verified_at' => now(),
                        'password_changed_at' => now(),
                    ]);

                    if (!empty($record['roles'])) {
                        $roleNames = array_map('trim', explode(',', $record['roles']));
                        $user->syncRoles($roleNames);
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'imported' => 0,
                    'errors' => $errors,
                    'message' => 'Import failed. No records were saved.',
                ], 422);
            }

            DB::commit();

            return response()->json([
                'imported' => $imported,
                'errors' => [],
                'message' => "Imported {$imported} users successfully."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('User import failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Import failed due to server error.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function syncUserPermissions(User $user, array $newPermissionIds, Request $request): void
    {
        $oldPermissions = $user->permissions->pluck('id')->toArray();
        $attached = array_diff($newPermissionIds, $oldPermissions);
        $detached = array_diff($oldPermissions, $newPermissionIds);

        $permissions = Permission::whereIn('id', $newPermissionIds)->get();

        $user->syncPermissions($permissions);

        foreach ($attached as $permId) {
            event(new PermissionChanged($user, 'attached', $permId, null, $request->ip(), $request->userAgent()));
        }
        foreach ($detached as $permId) {
            event(new PermissionChanged($user, 'detached', $permId, null, $request->ip(), $request->userAgent()));
        }
    }
}
