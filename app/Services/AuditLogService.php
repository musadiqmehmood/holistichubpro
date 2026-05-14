<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    public const SYSTEM_USER_ID = 1;

    /**
     * Log an action — direct INSERT, no queue needed.
     * Wrapped in try-catch so it never crashes the main request.
     */
    public static function log(
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $performedBy = null,
    ): void {
        try {
            $userId = self::resolveUserId($performedBy, $entityType, $newValues, $oldValues);

            if (!User::where('id', $userId)->exists()) {
                $userId = self::SYSTEM_USER_ID;
            }

            AuditLog::create([
                'action'       => $action,
                'entity_type'  => $entityType,
                'entity_id'    => $entityId,
                'performed_by' => $userId,
                'old_values'   => $oldValues,
                'new_values'   => $newValues,
                'ip_address'   => Request::ip() ?? '127.0.0.1',
                'user_agent'   => Request::userAgent() ?? 'System',
            ]);

        } catch (\Exception $e) {
            Log::error('AuditLogService::log failed', [
                'error'       => $e->getMessage(),
                'action'      => $action,
                'entity_type' => $entityType,
            ]);
        }
    }

    private static function resolveUserId(
        ?int $overrideId,
        string $entityType,
        ?array $newValues,
        ?array $oldValues,
    ): int {
        if ($overrideId !== null && $overrideId > 0) {
            return $overrideId;
        }

        $authId = Auth::id() ?: Auth::guard('sanctum')->id();
        if ($authId && $authId > 0) {
            return $authId;
        }

        if ($entityType === 'App\Models\User' || $entityType === User::class) {
            if (!empty($newValues['id']) && is_numeric($newValues['id'])) {
                return (int) $newValues['id'];
            }
            if (!empty($oldValues['id']) && is_numeric($oldValues['id'])) {
                return (int) $oldValues['id'];
            }
        }

        if (!empty($newValues['user_id']) && is_numeric($newValues['user_id'])) {
            return (int) $newValues['user_id'];
        }
        if (!empty($oldValues['user_id']) && is_numeric($oldValues['user_id'])) {
            return (int) $oldValues['user_id'];
        }

        return self::SYSTEM_USER_ID;
    }

    public static function logAsSystem(
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        self::log($action, $entityType, $entityId, $oldValues, $newValues, self::SYSTEM_USER_ID);
    }

    public static function logLogin(int $userId, array $context = []): void
    {
        self::log('login', User::class, $userId, null,
            array_merge(['logged_in_at' => now()->toDateTimeString()], $context), $userId);
    }

    public static function logLogout(int $userId): void
    {
        self::log('logout', User::class, $userId, null,
            ['logged_out_at' => now()->toDateTimeString()], $userId);
    }

    public static function logRoleAttached(int $userId, array $roleData, ?int $performedBy = null): void
    {
        self::log('role_attached', User::class, $userId, null, ['role' => $roleData], $performedBy);
    }

    public static function logRoleDetached(int $userId, array $roleData, ?int $performedBy = null): void
    {
        self::log('role_detached', User::class, $userId, ['role' => $roleData], null, $performedBy);
    }

    public static function logPermissionAttached(int $userId, array $permissionData, ?int $performedBy = null): void
    {
        self::log('permission_attached', User::class, $userId, null, ['permission' => $permissionData], $performedBy);
    }

    public static function logPermissionDetached(int $userId, array $permissionData, ?int $performedBy = null): void
    {
        self::log('permission_detached', User::class, $userId, ['permission' => $permissionData], null, $performedBy);
    }
}
