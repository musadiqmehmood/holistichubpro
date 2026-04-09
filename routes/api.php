<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Middleware\EnsurePasswordIsNotExpired;

// Public routes – REGISTER REMOVED (B-02)
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::prefix('admin')
        ->middleware([EnsurePasswordIsNotExpired::class]) // B-03
        ->group(function () {
            // Users
            Route::get('users', [UserController::class, 'index'])->middleware('can:users.view');
            Route::post('users', [UserController::class, 'store'])->middleware('can:users.create');
            Route::post('users/import', [UserController::class, 'import'])->middleware('can:users.create'); // ✅ NEW for import
            Route::get('users/{user}', [UserController::class, 'show'])->middleware('can:users.view');
            Route::put('users/{user}', [UserController::class, 'update'])->middleware('can:users.edit');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('can:users.delete');
            Route::get('users/{user}/audit-logs', [UserController::class, 'auditLogs'])->middleware('can:audit.view');
            // No export route – client-side export

            // Roles, Permissions, Branches (unchanged)
            Route::get('roles', [RoleController::class, 'index'])->middleware('can:roles.view');
            Route::post('roles', [RoleController::class, 'store'])->middleware('can:roles.create');
            Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('can:roles.view');
            Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('can:roles.edit');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete');

            Route::get('permissions', [PermissionController::class, 'index'])->middleware('can:permissions.view');
            Route::post('permissions', [PermissionController::class, 'store'])->middleware('can:permissions.create');
            Route::get('permissions/{permission}', [PermissionController::class, 'show'])->middleware('can:permissions.view');
            Route::put('permissions/{permission}', [PermissionController::class, 'update'])->middleware('can:permissions.edit');
            Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('can:permissions.delete');

            Route::get('branches', [BranchController::class, 'index'])->middleware('can:branches.view');
            Route::post('branches', [BranchController::class, 'store'])->middleware('can:branches.create');
            Route::get('branches/{branch}', [BranchController::class, 'show'])->middleware('can:branches.view');
            Route::put('branches/{branch}', [BranchController::class, 'update'])->middleware('can:branches.edit');
            Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->middleware('can:branches.delete');

            // Audit Logs – bulk-delete changed to POST (B-04)
            Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('can:audit.view');
            Route::get('audit-logs/filters', [AuditLogController::class, 'filters'])->middleware('can:audit.view');
            Route::get('audit-logs/stats', [AuditLogController::class, 'stats'])->middleware('can:audit.view');
            Route::delete('audit-logs/clear-all', [AuditLogController::class, 'clearAll'])->middleware('can:audit.delete');
            Route::post('audit-logs/bulk-delete', [AuditLogController::class, 'bulkDestroy'])->middleware('can:audit.delete');
            Route::delete('audit-logs/{auditLog}', [AuditLogController::class, 'destroy'])->middleware('can:audit.delete');
            // No export route – client-side export
        });
});
