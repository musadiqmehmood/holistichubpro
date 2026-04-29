<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne; // ADDED: Relationship to settings
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon; // ADDED: For dynamic formatting

class Branch extends Model
{
    use HasFactory, SoftDeletes;

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

    /**
     * ADDED: Link to branch-specific store settings.
     * This allows the branch to know its own date/time formats.
     */
    public function storeSetting(): HasOne
    {
        return $this->hasOne(StoreSetting::class);
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

    /**
     * UPDATED: Dynamic formatting based on branch settings.
     * CHANGE: Replaced hardcoded 'h:i A' with dynamic format from storeSetting.
     */
    public function getFormattedOpeningTimeAttribute(): ?string
    {
        if (!$this->opening_time) return null;

        $format = $this->storeSetting?->time_format ?? 'h:i A';
        try {
            return Carbon::parse($this->opening_time)->format($format);
        } catch (\Exception $e) {
            return date($format, strtotime($this->opening_time));
        }
    }

    public function getFormattedClosingTimeAttribute(): ?string
    {
        if (!$this->closing_time) return null;

        $format = $this->storeSetting?->time_format ?? 'h:i A';
        try {
            return Carbon::parse($this->closing_time)->format($format);
        } catch (\Exception $e) {
            return date($format, strtotime($this->closing_time));
        }
    }
}
