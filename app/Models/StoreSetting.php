<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StoreSetting extends Model
{
    use HasFactory;

    protected $table = 'store_settings';

    protected $fillable = [
        'store_code',
        'store_name',
        'mobile',
        'email',
        'phone',
        'gst_number',
        'tax_number',
        'pan_number',
        'store_website',
        'show_signature_on_invoice',
        'signature',
        'bank_details',
        'store_logo',
        'branch_id',
        'timezone',
        'date_format',
        'time_format',
        'currency',
        'currency_symbol_placement',
        'decimals',
        'decimals_for_quantity',
    ];

    protected $casts = [
        'show_signature_on_invoice' => 'boolean',
        'decimals'                  => 'integer',
        'decimals_for_quantity'     => 'integer',
    ];

    protected $appends = ['store_logo_url', 'signature_url', 'currency_object'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getStoreLogoUrlAttribute(): ?string
    {
        if (!$this->store_logo) {
            return null;
        }
        $relativePath = Storage::url($this->store_logo);
        return request()->getSchemeAndHttpHost() . $relativePath;
    }

    public function getSignatureUrlAttribute(): ?string
    {
        if (!$this->signature) {
            return null;
        }
        $relativePath = Storage::url($this->signature);
        return request()->getSchemeAndHttpHost() . $relativePath;
    }

    public function getCurrencyObjectAttribute(): ?Currency
    {
        if (!$this->currency) {
            return null;
        }
        return Currency::where('code', $this->currency)->first();
    }
}
