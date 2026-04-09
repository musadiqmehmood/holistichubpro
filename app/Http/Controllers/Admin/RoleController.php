<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use App\Services\AuditLogService;

// CRITICAL: Use Spatie's Role model
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
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

        return response()->json($roles); // Return array directly, not wrapped in object
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

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $guardName,
        ]);

        // Sync permissions if provided (by name)
        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // EXPLICIT AUDIT LOG - Using Service
        AuditLogService::log('created', Role::class, $role->id, null, [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role->load('permissions'),
        ], 201);
    }

    /**
     * Show single role with permissions
     */
    public function show(Role $role)
    {
        $this->authorize('view', $role);

        return response()->json([
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
            }),
        ]);
    }

    /**
     * Update role and permissions
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        // Prevent updating super-admin
        if ($role->name === 'super-admin') {
            return response()->json([
                'message' => 'Cannot modify system protected role',
            ], 403);
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

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // EXPLICIT AUDIT LOG - Using Service
        AuditLogService::log('updated', Role::class, $role->id, $oldValues, [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $validated['permissions'] ?? $oldValues['permissions'],
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role->load('permissions'),
        ]);
    }

    /**
     * Delete role
     */
    public function destroy(Request $request, Role $role)
    {
        $this->authorize('delete', $role);

        // Prevent deleting super-admin role
        if ($role->name === 'super-admin') {
            return response()->json([
                'message' => 'Cannot delete system protected role',
            ], 403);
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
            return response()->json([
                'message' => 'Cannot delete role with assigned users',
                'users_count' => $userCount,
            ], 409);
        }

        // EXPLICIT AUDIT LOG - Using Service
        AuditLogService::log('deleted', Role::class, $role->id, $oldValues, null);

        $role->delete();

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(['message' => 'Role deleted successfully']);
    }
}
