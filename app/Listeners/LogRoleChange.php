<?php

namespace App\Listeners;

use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

class LogRoleChange
{
    public function handle($event): void
    {
        try {
            $performedBy = Auth::id();
            $ipAddress = Request::ip() ?? '127.0.0.1';
            $userAgent = Request::userAgent() ?? 'Unknown';

            if ($event instanceof RoleAttached) {
                AuditLogService::logRoleAttached(
                    $event->model->id,
                    [
                        'id' => $event->role->id,
                        'name' => $event->role->name
                    ],
                    $performedBy
                );

            } elseif ($event instanceof RoleDetached) {
                AuditLogService::logRoleDetached(
                    $event->model->id,
                    [
                        'id' => $event->role->id,
                        'name' => $event->role->name
                    ],
                    $performedBy
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('LogRoleChange failed', [
                'error' => $e->getMessage(),
                'event_type' => get_class($event)
            ]);
        }
    }
}
