<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web'; // ✅ FIX B-11

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'branch_id',
        'email_verified_at', 'password_changed_at', 'failed_login_attempts',
        'locked_until', 'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'failed_login_attempts' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function isPasswordExpired(): bool
    {
        if (!$this->password_changed_at) return true;
        return $this->password_changed_at->diffInDays(now()) > 90;
    }

    public function wasPasswordUsedRecently(string $password): bool
    {
        return $this->passwordHistories()
            ->recent(5)
            ->get()
            ->contains(fn($history) => Hash::check($password, $history->password));
    }

    public function assignRole(...$roles)
    {
        if (is_null($this->branch_id)) {
            Log::warning('Cannot assign role: User has no branch_id', ['user_id' => $this->id]);
            throw new \Exception('User must have a branch assigned before assigning roles');
        }

        $roles = collect($roles)->flatten()->map(function ($role) {
            if (empty($role)) return null;
            if (is_numeric($role)) return Role::findById((int)$role, 'web');
            if (is_string($role)) return Role::findByName($role, 'web');
            return $role;
        })->filter();

        $syncData = [];
        foreach ($roles as $role) {
            $syncData[$role->id] = ['branch_id' => $this->branch_id];
        }
        $this->roles()->syncWithoutDetaching($syncData);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->load('roles');
        return $this;
    }

    public function syncRoles(...$roles)
    {
        if (is_null($this->branch_id)) {
            throw new \Exception('User must have a branch assigned before syncing roles');
        }
        $roles = collect($roles)->flatten()->map(function ($role) {
            if (empty($role)) return null;
            if (is_numeric($role)) return Role::findById((int)$role, 'web');
            if (is_string($role)) return Role::findByName($role, 'web');
            return $role;
        })->filter();

        $syncData = [];
        foreach ($roles as $role) {
            $syncData[$role->id] = ['branch_id' => $this->branch_id];
        }
        $this->roles()->sync($syncData);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->load('roles');
        return $this;
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'performed_by');
    }

    /**
     * Check if the user has a role scoped to a specific branch.
     * Falls back to regular hasRole() if no branch context is provided.
     */
    public function hasRoleInBranch($roles, ?int $branchId = null): bool
    {
        $branchId ??= app('branch_context')?->id;
        if (!$branchId) {
            return $this->hasRole($roles);
        }

        $roleNames = collect(is_array($roles) ? $roles : [$roles])
            ->filter(fn($r) => is_string($r) || is_numeric($r))
            ->map(fn($r) => (string) $r)
            ->toArray();

        if (empty($roleNames)) {
            return false;
        }

        return DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $this->id)
            ->where('model_has_roles.model_type', self::class)
            ->where('model_has_roles.branch_id', $branchId)
            ->whereIn('roles.name', $roleNames)
            ->exists();
    }

    /**
     * Check if the user has a permission via roles scoped to a specific branch.
     * Falls back to regular hasPermissionTo() if no branch context is provided.
     */
    public function hasPermissionInBranch($permission, ?int $branchId = null): bool
    {
        $branchId ??= app('branch_context')?->id;
        if (!$branchId) {
            return $this->checkPermissionTo($permission);
        }

        $permissionName = is_string($permission) ? $permission : $permission->name;

        return DB::table('role_has_permissions')
            ->join('model_has_roles', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('model_has_roles.model_id', $this->id)
            ->where('model_has_roles.model_type', self::class)
            ->where('model_has_roles.branch_id', $branchId)
            ->where('permissions.name', $permissionName)
            ->exists();
    }
}
