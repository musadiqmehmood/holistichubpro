<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    use HasFactory;

    protected $table = 'site_settings';

    protected $fillable = ['site_name', 'site_logo'];

    protected $appends = ['site_logo_url'];

    public function getSiteLogoUrlAttribute(): ?string
    {
        if (!$this->site_logo) {
            return null;
        }
        $relativePath = Storage::url($this->site_logo); // e.g. '/storage/settings/site/...'
        return request()->getSchemeAndHttpHost() . $relativePath;
    }
}
