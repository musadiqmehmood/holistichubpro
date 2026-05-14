<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use App\Services\AuditLogService;

// CRITICAL: Use Spatie's Permission model
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * List all permissions grouped by resource
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Permission::class);

        $perPage = (int) $request->input('per_page', 50);
        $perPage = min($perPage, 100);

        $permissions = Permission::with('roles')
            ->paginate($perPage)
            ->through(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'created_at' => $permission->created_at,
                    'updated_at' => $permission->updated_at,
                    'roles' => $permission->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                        ];
                    })->toArray(),
                ];
            });

        return response()->json($permissions);
    }

    /**
     * Store new permission
     */
    public function store(Request $request)
    {
        $this->authorize('create', Permission::class);

        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'guard_name' => 'nullable|string|in:web,api,sanctum',
        ]);

        $guardName = $validated['guard_name'] ?? 'web';

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => $guardName,
        ]);

        // EXPLICIT AUDIT LOG - Using Service
        AuditLogService::log('created', Permission::class, $permission->id, null, [
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
        ]);

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission,
        ], 201);
    }

    /**
     * Show single permission with roles
     */
    public function show(Permission $permission)
    {
        $this->authorize('view', $permission);

        return response()->json([
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
            'created_at' => $permission->created_at,
            'updated_at' => $permission->updated_at,
            'roles' => $permission->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),
        ]);
    }

    /**
     * Update permission
     */
    public function update(Request $request, Permission $permission)
    {
        $this->authorize('update', $permission);

        $oldValues = [
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
        ];

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:permissions,name,' . $permission->id,
            'guard_name' => 'nullable|string|in:web,api,sanctum',
        ]);

        $permission->update([
            'name' => $validated['name'] ?? $permission->name,
            'guard_name' => $validated['guard_name'] ?? $permission->guard_name,
        ]);

        // EXPLICIT AUDIT LOG - Using Service
        AuditLogService::log('updated', Permission::class, $permission->id, $oldValues, [
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
        ]);

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'message' => 'Permission updated successfully',
            'permission' => $permission,
        ]);
    }

    /**
     * Delete permission
     */
    public function destroy(Request $request, Permission $permission)
    {
        $this->authorize('delete', $permission);

        $oldValues = [
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
        ];

        // Check if permission is assigned to any roles
        $roleCount = DB::table('role_has_permissions')
            ->where('permission_id', $permission->id)
            ->count();

        if ($roleCount > 0) {
            return response()->json([
                'message' => 'Cannot delete permission assigned to roles',
                'roles_count' => $roleCount,
            ], 409);
        }

        // Check if permission is assigned directly to any users
        $userCount = DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->where('model_type', 'App\Models\User')
            ->count();

        if ($userCount > 0) {
            return response()->json([
                'message' => 'Cannot delete permission assigned directly to users',
                'users_count' => $userCount,
            ], 409);
        }

        // EXPLICIT AUDIT LOG - Using Service
        AuditLogService::log('deleted', Permission::class, $permission->id, $oldValues, null);

        $permission->delete();

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
