<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class TaxGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tax_groups';

    protected $fillable = ['name', 'tax_ids', 'calculated_percentage', 'status'];

    protected $casts = [
        'tax_ids'               => 'array',
        'calculated_percentage' => 'decimal:4',
        'status'                => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    /**
     * Returns the Tax models selected for this group.
     */
    public function taxes(): Collection
    {
        return Tax::whereIn('id', $this->tax_ids ?? [])->get(['id', 'name', 'percentage', 'status']);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Recompute calculated_percentage from current tax_ids.
     */
    public function recalculate(): void
    {
        $this->calculated_percentage = Tax::whereIn('id', $this->tax_ids ?? [])->sum('percentage');
        $this->saveQuietly();
    }

    /**
     * Append taxes_detail to the serialized model.
     */
    public function toArrayWithTaxes(): array
    {
        return array_merge($this->toArray(), ['taxes_detail' => $this->taxes()]);
    }
}
