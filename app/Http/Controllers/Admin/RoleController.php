<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\Traits\ApiResponse;

// CRITICAL: Use Spatie's Role model
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    use ApiResponse;
    /**
     * List all roles with their permissions
     */
    public function index()
    {
        $this->authorize('viewAny', Role::class);

        // CRITICAL FIX: Load permissions relationship and return as array
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                    ];
                })->toArray(), // Ensure this is an array
                'permissions_count' => $role->permissions->count(),
            ];
        });

        return $this->success($roles);
    }

    /**
     * Store new role with permissions
     */
    public function store(Request $request)
    {
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'guard_name' => 'nullable|string|in:web,api,sanctum',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name', // Validate permission names
        ]);

        $guardName = $validated['guard_name'] ?? 'web';

        $role = DB::transaction(function () use ($validated, $guardName) {
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => $guardName,
            ]);

            // Sync permissions if provided (by name)
            if (!empty($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }

            // EXPLICIT AUDIT LOG - Using Service
            AuditLogService::log('created', Role::class, $role->id, null, [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $validated['permissions'] ?? [],
            ]);

            return $role;
        });

        // Clear permission cache (outside transaction — harmless if stale)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return $this->created($role->load('permissions'), 'Role created successfully');
    }

    /**
     * Show single role with permissions
     */
    public function show(Role $role)
    {
        $this->authorize('view', $role);

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'created_at' => $role->created_at,
            'updated_at' => $role->updated_at,
            'permissions' => $role->permissions->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'guard_name' => $p->guard_name]),
        ]);
    }

    /**
     * Update role and permissions
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        // Prevent updating super-admin
        if ($role->name === config('rbac.super_admin_role')) {
            return $this->error('Cannot modify system protected role', 403);
        }

        $oldValues = [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ];

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:roles,name,' . $role->id,
            'guard_name' => 'nullable|string|in:web,api,sanctum',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name', // Validate permission names
        ]);

        DB::transaction(function () use ($role, $validated, $oldValues) {
            if (isset($validated['name'])) {
                $role->name = $validated['name'];
            }
            if (isset($validated['guard_name'])) {
                $role->guard_name = $validated['guard_name'];
            }
            $role->save();

            // Sync permissions if provided (by name)
            if (isset($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }

            // EXPLICIT AUDIT LOG - Using Service
            AuditLogService::log('updated', Role::class, $role->id, $oldValues, [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $validated['permissions'] ?? $oldValues['permissions'],
            ]);
        });

        // Clear cache (outside transaction — harmless if stale)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return $this->updated($role->load('permissions'), 'Role updated successfully');
    }

    /**
     * Delete role
     */
    public function destroy(Request $request, Role $role)
    {
        $this->authorize('delete', $role);

        // Prevent deleting super-admin role
        if ($role->name === config('rbac.super_admin_role')) {
            return $this->error('Cannot delete system protected role', 403);
        }

        $oldValues = [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ];

        // Check if role has users
        $userCount = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->count();

        if ($userCount > 0) {
            return $this->conflict('Cannot delete role with assigned users');
        }

        DB::transaction(function () use ($role, $oldValues) {
            // EXPLICIT AUDIT LOG - Using Service
            AuditLogService::log('deleted', Role::class, $role->id, $oldValues, null);

            $role->delete();
        });

        // Clear cache (outside transaction — harmless if stale)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return $this->deleted('Role deleted successfully');
    }
}
