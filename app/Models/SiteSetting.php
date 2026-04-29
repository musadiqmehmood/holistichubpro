<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // ADDED: For storage checks

class SiteSetting extends Model
{
    use HasFactory;

    protected $table = 'site_settings';

    protected $fillable = ['site_name', 'site_logo'];

    protected $appends = ['site_logo_url'];

    /**
     * UPDATED: Ensure the logo URL is always absolute and reliable.
     * CHANGE: Added asset() helper and existence check for storage.
     */
    public function getSiteLogoUrlAttribute(): ?string
    {
        if (!$this->site_logo) {
            return null;
        }

        // CHANGE: Check if it's already a full URL (e.g. from a seeder or external source)
        if (filter_var($this->site_logo, FILTER_VALIDATE_URL)) {
            return $this->site_logo;
        }

        // CHANGE: Use asset() to ensure absolute URL from storage
        if (Storage::disk('public')->exists($this->site_logo)) {
            return asset('storage/' . $this->site_logo);
        }

        return null;
    }
}
