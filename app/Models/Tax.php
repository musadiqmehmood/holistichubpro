<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tax extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'taxes';

    protected $fillable = ['name', 'percentage', 'status'];

    protected $casts = [
        'percentage' => 'decimal:4',
        'status'     => 'boolean',
    ];

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public static function activeList(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->orderBy('name')->get(['id', 'name', 'percentage']);
    }
}
