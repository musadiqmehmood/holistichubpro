<?php

namespace App\Listeners;

use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;

// REMOVED: ShouldQueue - runs synchronously to capture IP properly
class LogPermissionChange
{
    public function handle($event): void
    {
        try {
            // Capture context at execution time (not in constructor)
            $performedBy = Auth::id();
            $ipAddress = Request::ip() ?? '127.0.0.1';
            $userAgent = Request::userAgent() ?? 'Unknown';

            if ($event instanceof PermissionAttached) {
                AuditLogService::logPermissionAttached(
                    $event->model->id,
                    [
                        'id' => $event->permission->id,
                        'name' => $event->permission->name
                    ],
                    $performedBy
                );

            } elseif ($event instanceof PermissionDetached) {
                AuditLogService::logPermissionDetached(
                    $event->model->id,
                    [
                        'id' => $event->permission->id,
                        'name' => $event->permission->name
                    ],
                    $performedBy
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('LogPermissionChange failed', [
                'error' => $e->getMessage(),
                'event_type' => get_class($event)
            ]);
        }
    }
}
