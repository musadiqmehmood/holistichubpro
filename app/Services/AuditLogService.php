<?php

namespace App\Services;

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
     * Log an action with automatic user resolution and error handling
     *
     * @param string $action The action performed
     * @param string $entityType The model class being modified
     * @param int $entityId The ID of the entity
     * @param array|null $oldValues Previous state
     * @param array|null $newValues New state
     * @param int|null $performedBy Override user ID
     * @return AuditLog|null Returns null on failure (logs error)
     */
    public static function log(
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $performedBy = null
    ): ?AuditLog {
        try {
            $userId = self::resolveUserId($performedBy, $entityType, $newValues, $oldValues);

            // Validate user exists - Robust check for DB integrity
            if (!User::where('id', $userId)->exists()) {
                Log::warning("AuditLog: User ID {$userId} not found, using system user");
                $userId = self::SYSTEM_USER_ID;
            }

            return AuditLog::create([
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'performed_by' => $userId,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => Request::userAgent() ?? 'System',
            ]);
        } catch (\Exception $e) {
            Log::error('AuditLogService::log failed', [
                'error' => $e->getMessage(),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);
            return null;
        }
    }

    /**
     * Resolve user ID with multiple fallback strategies
     */
    private static function resolveUserId(
        ?int $overrideId,
        string $entityType,
        ?array $newValues,
        ?array $oldValues
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
        ?array $newValues = null
    ): ?AuditLog {
        return self::log($action, $entityType, $entityId, $oldValues, $newValues, self::SYSTEM_USER_ID);
    }

    /**
     * Log login event
     */
    public static function logLogin(int $userId, array $context = []): ?AuditLog
    {
        return self::log(
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
    public static function logLogout(int $userId): ?AuditLog
    {
        return self::log(
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
    public static function logRoleAttached(int $userId, array $roleData, ?int $performedBy = null): ?AuditLog
    {
        return self::log(
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
    public static function logRoleDetached(int $userId, array $roleData, ?int $performedBy = null): ?AuditLog
    {
        return self::log(
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
    public static function logPermissionAttached(int $userId, array $permissionData, ?int $performedBy = null): ?AuditLog
    {
        return self::log(
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
    public static function logPermissionDetached(int $userId, array $permissionData, ?int $performedBy = null): ?AuditLog
    {
        return self::log(
            'permission_detached',
            User::class,
            $userId,
            ['permission' => $permissionData],
            null,
            $performedBy
        );
    }
}
