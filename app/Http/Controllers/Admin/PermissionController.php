<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\Traits\ApiResponse;

// CRITICAL: Use Spatie's Permission model
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    use ApiResponse;
    /**
     * List all permissions grouped by resource
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Permission::class);

        // Use a very high per_page so ALL permissions are returned on one page.
        // Permissions are typically < 200 items; pagination would hide new ones.
        $perPage = (int) $request->input('per_page', 1000);
        $perPage = min($perPage, 1000);

        $permissions = Permission::with('roles')
            ->orderBy('created_at', 'desc')
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

        return $this->paginated($permissions);
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

        return $this->created($permission, 'Permission created successfully');
    }

    /**
     * Show single permission with roles
     */
    public function show(Permission $permission)
    {
        $this->authorize('view', $permission);

        return $this->success([
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
            'created_at' => $permission->created_at,
            'updated_at' => $permission->updated_at,
            'roles' => $permission->roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name]),
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

        return $this->updated($permission, 'Permission updated successfully');
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
            return $this->conflict('Cannot delete permission assigned to roles');
        }

        // Check if permission is assigned directly to any users
        $userCount = DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->where('model_type', 'App\\Models\\User')
            ->count();

        if ($userCount > 0) {
            return $this->conflict('Cannot delete permission assigned directly to users');
        }

        // EXPLICIT AUDIT LOG - Using Service
        AuditLogService::log('deleted', Permission::class, $permission->id, $oldValues, null);

        $permission->delete();

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return $this->deleted('Permission deleted successfully');
    }
}
