<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes; // ✅ ADDED

class Branch extends Model
{
    use HasFactory, SoftDeletes; // ✅ ADDED SoftDeletes

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'city',
        'state',
        'zip_code',
        'country',
        'is_active',
        'opening_time',
        'closing_time',
        'working_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'working_days' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function activeUsersCount(): int
    {
        return $this->users()->whereNotNull('email_verified_at')->count();
    }

    public function isOpenToday(): bool
    {
        $today = strtolower(now()->format('l'));
        return in_array($today, $this->working_days ?? []) && $this->is_active;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFormattedOpeningTimeAttribute(): ?string
    {
        return $this->opening_time ? date('h:i A', strtotime($this->opening_time)) : null;
    }

    public function getFormattedClosingTimeAttribute(): ?string
    {
        return $this->closing_time ? date('h:i A', strtotime($this->closing_time)) : null;
    }
}
