<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit.view'); // ✅ FIX B-12
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->can('audit.view');
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return $user->can('audit.delete'); // ✅ FIX B-12
    }
}
