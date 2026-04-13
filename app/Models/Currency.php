<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'currencies';

    protected $fillable = ['name', 'code', 'symbol', 'status'];

    protected $casts = ['status' => 'boolean'];

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper($value);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public static function activeCodes(): array
    {
        return static::active()->orderBy('name')->pluck('code')->toArray();
    }
}
