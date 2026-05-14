<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\AuditLogController;

use App\Http\Controllers\Admin\Settings\SettingsOptionsController;
use App\Http\Controllers\Admin\Settings\SiteSettingController;
use App\Http\Controllers\Admin\Settings\StoreSettingController;
use App\Http\Controllers\Admin\Settings\SmtpSettingController;
use App\Http\Controllers\Admin\Settings\TaxController;
use App\Http\Controllers\Admin\Settings\TaxGroupController;
use App\Http\Controllers\Admin\Settings\UnitController;
use App\Http\Controllers\Admin\Settings\PaymentTypeController;
use App\Http\Controllers\Admin\Settings\CurrencyController;
use App\Http\Controllers\Admin\Settings\BackupController;

use App\Http\Middleware\EnsurePasswordIsNotExpired;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::get('currencies/public', [CurrencyController::class, 'publicIndex']);

Route::post('/email/verification-notification', [AuthController::class, 'resendVerification']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('design-settings', [\App\Http\Controllers\UserDesignSettingController::class, 'show']);
    Route::put('design-settings', [\App\Http\Controllers\UserDesignSettingController::class, 'update']);

    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get('/user',             [AuthController::class, 'user']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::prefix('admin')
        ->middleware([EnsurePasswordIsNotExpired::class])
        ->group(function () {

            Route::get('users',                   [UserController::class, 'index'])->middleware('can:users.view');
            Route::post('users',                  [UserController::class, 'store'])->middleware('can:users.create');
            Route::post('users/import',           [UserController::class, 'import'])->middleware('can:users.create');
            Route::get('users/{user}',            [UserController::class, 'show'])->middleware('can:users.view');
            Route::put('users/{user}',            [UserController::class, 'update'])->middleware('can:users.edit');
            Route::delete('users/{user}',         [UserController::class, 'destroy'])->middleware('can:users.delete');
            Route::get('users/{user}/audit-logs', [UserController::class, 'auditLogs'])->middleware('can:audit.view');

            Route::get('roles',           [RoleController::class, 'index'])->middleware('can:roles.view');
            Route::post('roles',          [RoleController::class, 'store'])->middleware('can:roles.create');
            Route::get('roles/{role}',    [RoleController::class, 'show'])->middleware('can:roles.view');
            Route::put('roles/{role}',    [RoleController::class, 'update'])->middleware('can:roles.edit');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete');

            Route::get('permissions',                 [PermissionController::class, 'index'])->middleware('can:permissions.view');
            Route::post('permissions',                [PermissionController::class, 'store'])->middleware('can:permissions.create');
            Route::get('permissions/{permission}',    [PermissionController::class, 'show'])->middleware('can:permissions.view');
            Route::put('permissions/{permission}',    [PermissionController::class, 'update'])->middleware('can:permissions.edit');
            Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('can:permissions.delete');

            Route::get('branches',            [BranchController::class, 'index'])->middleware('can:branches.view');
            Route::post('branches',           [BranchController::class, 'store'])->middleware('can:branches.create');
            Route::get('branches/{branch}',   [BranchController::class, 'show'])->middleware('can:branches.view');
            Route::put('branches/{branch}',   [BranchController::class, 'update'])->middleware('can:branches.edit');
            Route::delete('branches/{branch}',[BranchController::class, 'destroy'])->middleware('can:branches.delete');

            Route::get('audit-logs/filters',       [AuditLogController::class, 'filters'])->middleware('can:audit.view');
            Route::get('audit-logs/stats',         [AuditLogController::class, 'stats'])->middleware('can:audit.view');
            Route::delete('audit-logs/clear-all',  [AuditLogController::class, 'clearAll'])->middleware('can:audit.delete');
            Route::post('audit-logs/bulk-delete',  [AuditLogController::class, 'bulkDestroy'])->middleware('can:audit.delete');
            Route::get('audit-logs',               [AuditLogController::class, 'index'])->middleware('can:audit.view');
            Route::delete('audit-logs/{auditLog}', [AuditLogController::class, 'destroy'])->middleware('can:audit.delete');

            Route::get('settings/options', SettingsOptionsController::class)->middleware('can:settings.view');

            Route::get('site-settings',        [SiteSettingController::class,  'show'])->middleware('can:settings.view');
            Route::post('site-settings',       [SiteSettingController::class,  'update'])->middleware('can:settings.manage');

            Route::get('store-settings',       [StoreSettingController::class, 'show'])->middleware('can:settings.view');
            Route::post('store-settings',      [StoreSettingController::class, 'update'])->middleware('can:settings.manage');

            Route::get('smtp-settings',        [SmtpSettingController::class,  'show'])->middleware('can:settings.view');
            Route::put('smtp-settings',        [SmtpSettingController::class,  'update'])->middleware('can:settings.manage');
            Route::post('smtp-settings/test',  [SmtpSettingController::class,  'test'])->middleware('can:settings.manage');

            Route::get('taxes/export',   [TaxController::class, 'export'])->middleware('can:taxes.view');
            Route::post('taxes/import',  [TaxController::class, 'import'])->middleware('can:taxes.create');
            Route::get('taxes',          [TaxController::class, 'index'])->middleware('can:taxes.view');
            Route::post('taxes',         [TaxController::class, 'store'])->middleware('can:taxes.create');
            Route::get('taxes/{tax}',    [TaxController::class, 'show'])->middleware('can:taxes.view');
            Route::put('taxes/{tax}',    [TaxController::class, 'update'])->middleware('can:taxes.edit');
            Route::delete('taxes/{tax}', [TaxController::class, 'destroy'])->middleware('can:taxes.delete');

            Route::get('tax-groups',               [TaxGroupController::class, 'index'])->middleware('can:tax_groups.view');
            Route::post('tax-groups',              [TaxGroupController::class, 'store'])->middleware('can:tax_groups.create');
            Route::get('tax-groups/{taxGroup}',    [TaxGroupController::class, 'show'])->middleware('can:tax_groups.view');
            Route::put('tax-groups/{taxGroup}',    [TaxGroupController::class, 'update'])->middleware('can:tax_groups.edit');
            Route::delete('tax-groups/{taxGroup}', [TaxGroupController::class, 'destroy'])->middleware('can:tax_groups.delete');

            Route::get('units/export',   [UnitController::class, 'export'])->middleware('can:units.view');
            Route::post('units/import',  [UnitController::class, 'import'])->middleware('can:units.create');
            Route::get('units',          [UnitController::class, 'index'])->middleware('can:units.view');
            Route::post('units',         [UnitController::class, 'store'])->middleware('can:units.create');
            Route::get('units/{unit}',   [UnitController::class, 'show'])->middleware('can:units.view');
            Route::put('units/{unit}',   [UnitController::class, 'update'])->middleware('can:units.edit');
            Route::delete('units/{unit}',[UnitController::class, 'destroy'])->middleware('can:units.delete');

            Route::get('payment-types/export',           [PaymentTypeController::class, 'export'])->middleware('can:payment_types.view');
            Route::post('payment-types/import',          [PaymentTypeController::class, 'import'])->middleware('can:payment_types.create');
            Route::get('payment-types',                  [PaymentTypeController::class, 'index'])->middleware('can:payment_types.view');
            Route::post('payment-types',                 [PaymentTypeController::class, 'store'])->middleware('can:payment_types.create');
            Route::get('payment-types/{paymentType}',    [PaymentTypeController::class, 'show'])->middleware('can:payment_types.view');
            Route::put('payment-types/{paymentType}',    [PaymentTypeController::class, 'update'])->middleware('can:payment_types.edit');
            Route::delete('payment-types/{paymentType}', [PaymentTypeController::class, 'destroy'])->middleware('can:payment_types.delete');

            Route::get('currencies/export',        [CurrencyController::class, 'export'])->middleware('can:currencies.view');
            Route::post('currencies/import',       [CurrencyController::class, 'import'])->middleware('can:currencies.create');
            Route::get('currencies',               [CurrencyController::class, 'index'])->middleware('can:currencies.view');
            Route::post('currencies',              [CurrencyController::class, 'store'])->middleware('can:currencies.create');
            Route::get('currencies/{currency}',    [CurrencyController::class, 'show'])->middleware('can:currencies.view');
            Route::put('currencies/{currency}',    [CurrencyController::class, 'update'])->middleware('can:currencies.edit');
            Route::delete('currencies/{currency}', [CurrencyController::class, 'destroy'])->middleware('can:currencies.delete');

            Route::post('backup',                 [BackupController::class, 'create'])->middleware('role:' . config('rbac.super_admin_role'));
            Route::get('backups',                 [BackupController::class, 'index'])->middleware('role:' . config('rbac.super_admin_role'));
            Route::get('backups/{file}/download', [BackupController::class, 'download'])->middleware('role:' . config('rbac.super_admin_role'));
            Route::delete('backups/{file}',       [BackupController::class, 'destroy'])->middleware('role:' . config('rbac.super_admin_role'));
        });
});
