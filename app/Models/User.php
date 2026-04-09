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
}
