<?php

namespace App\Services;

use App\Jobs\LogAuditJob;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /**
     * System user ID for automated/system operations
     */
    public const SYSTEM_USER_ID = 1;

    /**
     * Log an action asynchronously via queue.
     * The audit record is dispatched to the 'audit-logs' queue —
     * the request thread never blocks on the INSERT.
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

            LogAuditJob::dispatch(
                action: $action,
                entityType: $entityType,
                entityId: $entityId,
                oldValues: $oldValues,
                newValues: $newValues,
                performedBy: $userId,
                ipAddress: Request::ip() ?? '127.0.0.1',
                userAgent: Request::userAgent() ?? 'System',
            )->onQueue('audit-logs');
        } catch (\Exception $e) {
            Log::error('AuditLogService::log failed to dispatch', [
                'error'       => $e->getMessage(),
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
            ]);
        }
    }

    /**
     * Resolve user ID with multiple fallback strategies
     */
    private static function resolveUserId(
        ?int $overrideId,
        string $entityType,
        ?array $newValues,
        ?array $oldValues,
    ): int {
        // Priority 1: Explicit override (must be valid positive integer)
        if ($overrideId !== null && $overrideId > 0 && is_int($overrideId)) {
            return $overrideId;
        }

        // Priority 2: Currently authenticated user (Check Sanctum specifically for API)
        $authId = Auth::id() ?: Auth::guard('sanctum')->id();
        if ($authId && $authId > 0) {
            return $authId;
        }

        // Priority 3: For User entity updates, use the user being modified
        if ($entityType === 'App\Models\User' || $entityType === User::class) {
            if (!empty($newValues['id']) && is_numeric($newValues['id'])) {
                return (int) $newValues['id'];
            }
            if (!empty($oldValues['id']) && is_numeric($oldValues['id'])) {
                return (int) $oldValues['id'];
            }
        }

        // Priority 4: Try to extract user_id from values (often found in pivot table data)
        if (!empty($newValues['user_id']) && is_numeric($newValues['user_id'])) {
            return (int) $newValues['user_id'];
        }
        if (!empty($oldValues['user_id']) && is_numeric($oldValues['user_id'])) {
            return (int) $oldValues['user_id'];
        }

        // Final fallback: System user (ID: 1)
        return self::SYSTEM_USER_ID;
    }

    /**
     * Log with explicit system user (for queue jobs, commands)
     */
    public static function logAsSystem(
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        self::log($action, $entityType, $entityId, $oldValues, $newValues, self::SYSTEM_USER_ID);
    }

    /**
     * Log login event
     */
    public static function logLogin(int $userId, array $context = []): void
    {
        self::log(
            'login',
            User::class,
            $userId,
            null,
            array_merge(['logged_in_at' => now()->toDateTimeString()], $context),
            $userId
        );
    }

    /**
     * Log logout event
     */
    public static function logLogout(int $userId): void
    {
        self::log(
            'logout',
            User::class,
            $userId,
            null,
            ['logged_out_at' => now()->toDateTimeString()],
            $userId
        );
    }

    /**
     * Log role attachment
     */
    public static function logRoleAttached(int $userId, array $roleData, ?int $performedBy = null): void
    {
        self::log(
            'role_attached',
            User::class,
            $userId,
            null,
            ['role' => $roleData],
            $performedBy
        );
    }

    /**
     * Log role detachment
     */
    public static function logRoleDetached(int $userId, array $roleData, ?int $performedBy = null): void
    {
        self::log(
            'role_detached',
            User::class,
            $userId,
            ['role' => $roleData],
            null,
            $performedBy
        );
    }

    /**
     * Log permission attachment
     */
    public static function logPermissionAttached(int $userId, array $permissionData, ?int $performedBy = null): void
    {
        self::log(
            'permission_attached',
            User::class,
            $userId,
            null,
            ['permission' => $permissionData],
            $performedBy
        );
    }

    /**
     * Log permission detachment
     */
    public static function logPermissionDetached(int $userId, array $permissionData, ?int $performedBy = null): void
    {
        self::log(
            'permission_detached',
            User::class,
            $userId,
            ['permission' => $permissionData],
            null,
            $performedBy
        );
    }
}
