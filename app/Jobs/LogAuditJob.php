<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LogAuditJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public string $action,
        public string $entityType,
        public int $entityId,
        public ?array $oldValues,
        public ?array $newValues,
        public int $performedBy,
        public string $ipAddress,
        public string $userAgent,
    ) {
    }

    public function handle(): void
    {
        // Validate user exists — fallback to system user if deleted between dispatch and execution
        $userId = $this->performedBy;
        if (!User::where('id', $userId)->exists()) {
            Log::warning("AuditLogJob: User ID {$userId} not found, using system user");
            $userId = 1;
        }

        AuditLog::create([
            'action'       => $this->action,
            'entity_type'  => $this->entityType,
            'entity_id'    => $this->entityId,
            'performed_by' => $userId,
            'old_values'   => $this->oldValues,
            'new_values'   => $this->newValues,
            'ip_address'   => $this->ipAddress,
            'user_agent'   => $this->userAgent,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AuditLogJob failed permanently', [
            'error'       => $exception->getMessage(),
            'action'      => $this->action,
            'entity_type' => $this->entityType,
            'entity_id'   => $this->entityId,
        ]);
    }
}
