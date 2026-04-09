<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action', 'entity_type', 'entity_id', 'performed_by',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ REMOVED: protected $attributes = ['performed_by' => 1];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($auditLog) {
            if (empty($auditLog->performed_by) || !is_numeric($auditLog->performed_by) || $auditLog->performed_by <= 0) {
                $auditLog->performed_by = 1;
                Log::warning('AuditLog: Invalid performed_by, defaulted to system user', [
                    'action' => $auditLog->action,
                    'entity_type' => $auditLog->entity_type,
                ]);
            }
            if (empty($auditLog->ip_address)) {
                $auditLog->ip_address = '127.0.0.1';
            }
            if (empty($auditLog->user_agent)) {
                $auditLog->user_agent = 'Unknown';
            }
        });
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getPerformerNameAttribute(): string
    {
        return $this->performer?->name ?? 'System';
    }
}
