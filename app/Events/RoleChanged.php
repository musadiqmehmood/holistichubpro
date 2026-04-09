<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $action; // attached or detached
    public $roleId;
    public $roleName;
    public $performedBy;
    public $ipAddress;
    public $userAgent;

    /**
     * Create a new event instance.
     *
     * CRITICAL: IP and user agent are captured HERE before queue processing
     */
    public function __construct(
        User $user,
        string $action,
        int $roleId,
        ?string $roleName = null
    ) {
        $this->user = $user;
        $this->action = $action;
        $this->roleId = $roleId;
        $this->roleName = $roleName;

        // Capture these BEFORE dispatching to queue - they will be null in queue context
        $this->performedBy = auth()->id();
        $this->ipAddress = request()->ip() ?? '127.0.0.1';
        $this->userAgent = request()->userAgent() ?? 'Unknown';
    }
}
